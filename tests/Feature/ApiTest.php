<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Post;
use App\Models\Tour;
use App\Models\Donation;
use App\Models\NewsletterSubscriber;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_index_returns_success()
    {
        Post::factory()->create(['slug' => 'test-post']);
        $response = $this->getJson('/api/posts');
        $response->assertStatus(200)->assertJsonStructure(['posts']);
    }

    public function test_posts_show_returns_success()
    {
        $post = Post::factory()->create(['slug' => 'test-post']);
        $response = $this->getJson('/api/posts/test-post');
        $response->assertStatus(200)->assertJsonStructure(['post']);
    }

    public function test_tours_index_returns_success()
    {
        Tour::factory()->create(['slug' => 'test-tour']);
        $response = $this->getJson('/api/tours');
        $response->assertStatus(200)->assertJsonStructure(['tours']);
    }

    public function test_tours_show_returns_success()
    {
        $tour = Tour::factory()->create(['slug' => 'test-tour']);
        $response = $this->getJson('/api/tours/test-tour');
        $response->assertStatus(200)->assertJsonStructure(['tour']);
    }

    public function test_donation_create_intent_and_confirm()
    {
        $response = $this->postJson('/api/donations/create-intent', [
            'amount' => 10.00,
            'donor_name' => 'Test Donor',
            'donor_email' => 'donor@example.com',
        ]);
        $response->assertStatus(200)->assertJsonStructure(['clientSecret', 'donation_id']);
        $donationId = $response->json('donation_id');
        $confirm = $this->postJson('/api/donations/confirm', ['donation_id' => $donationId]);
        $confirm->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_newsletter_subscribe()
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'subscriber@example.com',
            'name' => 'Test Subscriber',
            'interests' => 'art,news',
        ]);
        $response->assertStatus(200)->assertJsonStructure(['subscribed', 'subscriber_id']);
    }

    public function test_contact_send()
    {
        $response = $this->postJson('/api/contact/send', [
            'name' => 'Contact Name',
            'email' => 'contact@example.com',
            'message' => 'Hello!'
        ]);
        $response->assertStatus(200)->assertJson(['sent' => true]);
    }

    public function test_pages_landing_and_site_info()
    {
        $this->getJson('/api/pages/landing')->assertStatus(200)->assertJsonStructure(['hero', 'features', 'snapshot', 'tours_preview', 'why_artists', 'cta', 'donation_ribbon']);
        $this->getJson('/api/site-info')->assertStatus(200)->assertJsonStructure(['name', 'mission', 'contact_email', 'social']);
    }
}
