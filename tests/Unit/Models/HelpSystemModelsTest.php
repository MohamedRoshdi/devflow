<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\HelpContent;
use App\Models\HelpContentRelated;
use App\Models\HelpContentTranslation;
use App\Models\HelpInteraction;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class HelpSystemModelsTest extends TestCase
{
    // ========================
    // HelpContent Model Tests
    // ========================

    /** @test */
    public function help_content_can_be_created_with_factory(): void
    {
        $helpContent = HelpContent::factory()->create();

        $this->assertInstanceOf(HelpContent::class, $helpContent);
        $this->assertDatabaseHas('help_contents', [
            'id' => $helpContent->id,
        ]);
    }

    /** @test */
    public function help_content_casts_details_as_array(): void
    {
        $details = ['step1' => 'First step', 'step2' => 'Second step'];
        $helpContent = HelpContent::factory()->create(['details' => $details]);

        $this->assertIsArray($helpContent->details);
        $this->assertEquals($details, $helpContent->details);
    }

    /** @test */
    public function help_content_casts_is_active_as_boolean(): void
    {
        $helpContent = HelpContent::factory()->create(['is_active' => true]);

        $this->assertTrue($helpContent->is_active);
        $this->assertIsBool($helpContent->is_active);
    }

    /** @test */
    public function help_content_has_many_translations(): void
    {
        $helpContent = HelpContent::factory()->create();
        HelpContentTranslation::factory()->count(3)->create(['help_content_id' => $helpContent->id]);

        $this->assertCount(3, $helpContent->translations);
        $this->assertInstanceOf(HelpContentTranslation::class, $helpContent->translations->first());
    }

    /** @test */
    public function help_content_has_many_interactions(): void
    {
        $helpContent = HelpContent::factory()->create();
        HelpInteraction::factory()->count(2)->create(['help_content_id' => $helpContent->id]);

        $this->assertCount(2, $helpContent->interactions);
        $this->assertInstanceOf(HelpInteraction::class, $helpContent->interactions->first());
    }

    /** @test */
    public function help_content_has_many_related_contents(): void
    {
        $helpContent = HelpContent::factory()->create();
        HelpContentRelated::factory()->count(3)->create(['help_content_id' => $helpContent->id]);

        $this->assertCount(3, $helpContent->relatedContents);
        $this->assertInstanceOf(HelpContentRelated::class, $helpContent->relatedContents->first());
    }

    /** @test */
    public function help_content_scope_active_filters_active_content(): void
    {
        HelpContent::factory()->create(['is_active' => true]);
        HelpContent::factory()->create(['is_active' => true]);
        HelpContent::factory()->create(['is_active' => false]);

        $active = HelpContent::active()->get();
        $this->assertCount(2, $active);
    }

    /** @test */
    public function help_content_scope_by_category_filters_by_category(): void
    {
        HelpContent::factory()->count(2)->create(['category' => 'deployment']);
        HelpContent::factory()->create(['category' => 'server']);
        HelpContent::factory()->create(['category' => 'deployment']);

        $deploymentHelp = HelpContent::byCategory('deployment')->get();
        $this->assertCount(3, $deploymentHelp);
    }

    /** @test */
    public function help_content_scope_search_finds_in_title(): void
    {
        HelpContent::factory()->create(['title' => 'How to deploy a project']);
        HelpContent::factory()->create(['title' => 'Server configuration']);
        HelpContent::factory()->create(['title' => 'Deployment troubleshooting']);

        $results = HelpContent::search('deploy')->get();
        $this->assertCount(2, $results);
    }

    /** @test */
    public function help_content_scope_search_finds_in_brief(): void
    {
        HelpContent::factory()->create(['brief' => 'Learn about database migrations']);
        HelpContent::factory()->create(['brief' => 'Setup your server']);
        HelpContent::factory()->create(['brief' => 'Database backup strategies']);

        $results = HelpContent::search('database')->get();
        $this->assertCount(2, $results);
    }

    /** @test */
    public function help_content_scope_search_finds_in_key(): void
    {
        HelpContent::factory()->create(['key' => 'deployment.setup']);
        HelpContent::factory()->create(['key' => 'server.config']);
        HelpContent::factory()->create(['key' => 'deployment.troubleshoot']);

        $results = HelpContent::search('deployment')->get();
        $this->assertCount(2, $results);
    }

    /** @test */
    public function help_content_get_localized_brief_returns_english_by_default(): void
    {
        $helpContent = HelpContent::factory()->create(['brief' => 'English brief']);

        App::setLocale('en');
        $this->assertEquals('English brief', $helpContent->getLocalizedBrief());
    }

    /** @test */
    public function help_content_get_localized_brief_returns_translation_when_available(): void
    {
        $helpContent = HelpContent::factory()->create(['brief' => 'English brief']);
        HelpContentTranslation::factory()->create([
            'help_content_id' => $helpContent->id,
            'locale' => 'fr',
            'brief' => 'French brief',
        ]);

        App::setLocale('fr');
        $this->assertEquals('French brief', $helpContent->getLocalizedBrief());
    }

    /** @test */
    public function help_content_get_localized_brief_falls_back_to_english(): void
    {
        $helpContent = HelpContent::factory()->create(['brief' => 'English brief']);

        App::setLocale('es');
        $this->assertEquals('English brief', $helpContent->getLocalizedBrief());
    }

    /** @test */
    public function help_content_get_localized_details_returns_english_by_default(): void
    {
        $details = ['step1' => 'First', 'step2' => 'Second'];
        $helpContent = HelpContent::factory()->create(['details' => $details]);

        App::setLocale('en');
        $this->assertEquals($details, $helpContent->getLocalizedDetails());
    }

    /** @test */
    public function help_content_get_localized_details_returns_translation_when_available(): void
    {
        $englishDetails = ['step1' => 'First', 'step2' => 'Second'];
        $frenchDetails = ['step1' => 'Premier', 'step2' => 'Deuxième'];

        $helpContent = HelpContent::factory()->create(['details' => $englishDetails]);
        HelpContentTranslation::factory()->create([
            'help_content_id' => $helpContent->id,
            'locale' => 'fr',
            'details' => $frenchDetails,
        ]);

        App::setLocale('fr');
        $this->assertEquals($frenchDetails, $helpContent->getLocalizedDetails());
    }

    /** @test */
    public function help_content_increment_view_count_increases_count(): void
    {
        $helpContent = HelpContent::factory()->create(['view_count' => 0]);

        $helpContent->incrementViewCount();
        $helpContent->refresh();

        $this->assertEquals(1, $helpContent->view_count);
    }

    /** @test */
    public function help_content_mark_helpful_increases_helpful_count(): void
    {
        $helpContent = HelpContent::factory()->create(['helpful_count' => 0]);

        $helpContent->markHelpful();
        $helpContent->refresh();

        $this->assertEquals(1, $helpContent->helpful_count);
    }

    /** @test */
    public function help_content_mark_not_helpful_increases_not_helpful_count(): void
    {
        $helpContent = HelpContent::factory()->create(['not_helpful_count' => 0]);

        $helpContent->markNotHelpful();
        $helpContent->refresh();

        $this->assertEquals(1, $helpContent->not_helpful_count);
    }

    /** @test */
    public function help_content_get_helpfulness_percentage_calculates_correctly(): void
    {
        $helpContent = HelpContent::factory()->create([
            'helpful_count' => 8,
            'not_helpful_count' => 2,
        ]);

        $this->assertEquals(80.0, $helpContent->getHelpfulnessPercentage());
    }

    /** @test */
    public function help_content_get_helpfulness_percentage_returns_zero_when_no_votes(): void
    {
        $helpContent = HelpContent::factory()->create([
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ]);

        $this->assertEquals(0, $helpContent->getHelpfulnessPercentage());
    }

    // ========================
    // HelpContentTranslation Model Tests
    // ========================

    /** @test */
    public function help_content_translation_can_be_created_with_factory(): void
    {
        $translation = HelpContentTranslation::factory()->create();

        $this->assertInstanceOf(HelpContentTranslation::class, $translation);
        $this->assertDatabaseHas('help_content_translations', [
            'id' => $translation->id,
        ]);
    }

    /** @test */
    public function help_content_translation_belongs_to_help_content(): void
    {
        $helpContent = HelpContent::factory()->create();
        $translation = HelpContentTranslation::factory()->create(['help_content_id' => $helpContent->id]);

        $this->assertInstanceOf(HelpContent::class, $translation->helpContent);
        $this->assertEquals($helpContent->id, $translation->helpContent->id);
    }

    /** @test */
    public function help_content_translation_casts_details_as_array(): void
    {
        $details = ['step1' => 'Premier pas', 'step2' => 'Deuxième pas'];
        $translation = HelpContentTranslation::factory()->create(['details' => $details]);

        $this->assertIsArray($translation->details);
        $this->assertEquals($details, $translation->details);
    }

    // ========================
    // HelpContentRelated Model Tests
    // ========================

    /** @test */
    public function help_content_related_can_be_created_with_factory(): void
    {
        $related = HelpContentRelated::factory()->create();

        $this->assertInstanceOf(HelpContentRelated::class, $related);
        $this->assertDatabaseHas('help_content_related', [
            'id' => $related->id,
        ]);
    }

    /** @test */
    public function help_content_related_belongs_to_help_content(): void
    {
        $helpContent = HelpContent::factory()->create();
        $related = HelpContentRelated::factory()->create(['help_content_id' => $helpContent->id]);

        $this->assertInstanceOf(HelpContent::class, $related->helpContent);
        $this->assertEquals($helpContent->id, $related->helpContent->id);
    }

    /** @test */
    public function help_content_related_belongs_to_related_help_content(): void
    {
        $relatedHelpContent = HelpContent::factory()->create();
        $related = HelpContentRelated::factory()->create(['related_help_content_id' => $relatedHelpContent->id]);

        $this->assertInstanceOf(HelpContent::class, $related->relatedHelpContent);
        $this->assertEquals($relatedHelpContent->id, $related->relatedHelpContent->id);
    }

    /** @test */
    public function help_content_related_casts_relevance_score_as_float(): void
    {
        $related = HelpContentRelated::factory()->create(['relevance_score' => 0.85]);

        $this->assertIsFloat($related->relevance_score);
        $this->assertEquals(0.85, $related->relevance_score);
    }

    // ========================
    // HelpInteraction Model Tests
    // ========================

    /** @test */
    public function help_interaction_can_be_created_with_factory(): void
    {
        $interaction = HelpInteraction::factory()->create();

        $this->assertInstanceOf(HelpInteraction::class, $interaction);
        $this->assertDatabaseHas('help_interactions', [
            'id' => $interaction->id,
        ]);
    }

    /** @test */
    public function help_interaction_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $interaction = HelpInteraction::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $interaction->user);
        $this->assertEquals($user->id, $interaction->user->id);
    }

    /** @test */
    public function help_interaction_belongs_to_help_content(): void
    {
        $helpContent = HelpContent::factory()->create();
        $interaction = HelpInteraction::factory()->create(['help_content_id' => $helpContent->id]);

        $this->assertInstanceOf(HelpContent::class, $interaction->helpContent);
        $this->assertEquals($helpContent->id, $interaction->helpContent->id);
    }

    /** @test */
    public function help_interaction_stores_interaction_type(): void
    {
        $interaction = HelpInteraction::factory()->create(['interaction_type' => 'viewed']);

        $this->assertEquals('viewed', $interaction->interaction_type);
    }

    /** @test */
    public function help_interaction_stores_ip_address_and_user_agent(): void
    {
        $interaction = HelpInteraction::factory()->create([
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $this->assertEquals('192.168.1.100', $interaction->ip_address);
        $this->assertEquals('Mozilla/5.0', $interaction->user_agent);
    }
}
