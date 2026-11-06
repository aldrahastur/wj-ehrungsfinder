<?php

use App\Models\Honor;
use App\Models\Organisation;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

test('guests are redirected to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('users without role are forbidden', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
});

test('admin can open all admin pages', function () {
    $admin = User::factory()->admin()->create();
    $honor = Honor::firstWhere('code', 'jci-senatorenwuerde');

    $this->actingAs($admin);

    foreach ([
        route('dashboard'),
        route('admin.honors.index'),
        route('admin.honors.edit', $honor),
        route('admin.rules.show', $honor->publishedRule),
        route('admin.questions'),
        route('admin.reference-cases'),
        route('admin.sources'),
        route('admin.feedback'),
        route('admin.users'),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

test('editor cannot manage users', function () {
    $editor = User::factory()->create();
    $editor->organisations()->attach(Organisation::firstWhere('code', 'wj-st'), ['role' => User::ROLE_EDITOR]);

    $this->actingAs($editor)->get(route('dashboard'))->assertOk();
    $this->actingAs($editor)->get(route('admin.users'))->assertForbidden();
});

test('editor of other package cannot edit honor', function () {
    $editor = User::factory()->create();
    $editor->organisations()->attach(Organisation::firstWhere('code', 'wj-st'), ['role' => User::ROLE_EDITOR]);
    $honor = Honor::firstWhere('code', 'jci-senatorenwuerde');

    $this->actingAs($editor);

    Livewire::test('pages::admin.honors.edit', ['honor' => $honor])->call('newDraft')->assertForbidden();
});

test('admin can create draft and save criteria', function () {
    $admin = User::factory()->admin()->create();
    $honor = Honor::firstWhere('code', 'wj-st-goldene-ehrennadel');
    $this->actingAs($admin);

    Livewire::test('pages::admin.honors.edit', ['honor' => $honor])->call('newDraft');
    $draft = $honor->ruleVersions()->first();

    Livewire::test('pages::admin.rules.show', ['ruleVersion' => $draft])
        ->set('criteriaJson', '{"question": "q_unbekannt"}')
        ->call('save')
        ->assertHasErrors('criteriaJson')
        ->set('criteriaJson', '{"all": [{"question": "q_lsa"}, {"prior": "q_hat_lsa", "mode": "unclear"}]}')
        ->call('save')
        ->assertHasNoErrors();

    expect($draft->fresh()->criteria['all'][0]['question'])->toBe('q_lsa');
});
