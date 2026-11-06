<?php

namespace Tests\Feature\Ehrungsfinder;

use App\Models\Honor;
use App\Models\Organisation;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_users_without_role_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
    }

    public function test_admin_can_open_all_admin_pages(): void
    {
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
    }

    public function test_editor_cannot_manage_users(): void
    {
        $editor = User::factory()->create();
        $editor->organisations()->attach(Organisation::firstWhere('code', 'wj-st'), ['role' => User::ROLE_EDITOR]);

        $this->actingAs($editor)->get(route('dashboard'))->assertOk();
        $this->actingAs($editor)->get(route('admin.users'))->assertForbidden();
    }

    public function test_editor_of_other_package_cannot_edit_honor(): void
    {
        $editor = User::factory()->create();
        $editor->organisations()->attach(Organisation::firstWhere('code', 'wj-st'), ['role' => User::ROLE_EDITOR]);
        $honor = Honor::firstWhere('code', 'jci-senatorenwuerde');

        $this->actingAs($editor);
        Livewire::test('pages::admin.honors.edit', ['honor' => $honor])->call('newDraft')->assertForbidden();
    }

    public function test_admin_can_create_draft_and_save_criteria(): void
    {
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

        $this->assertSame('q_lsa', $draft->fresh()->criteria['all'][0]['question']);
    }
}
