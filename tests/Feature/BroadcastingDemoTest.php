<?php

use App\Events\ChatMessageSent;
use App\Events\OrderStatusUpdated;
use App\Events\PublicAnnouncementMade;
use App\Models\Post;
use App\Models\User;
use App\Notifications\DemoNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $this->post = Post::factory()->create(['user_id' => $this->user->id]);
});

test('login route authenticates demo user and redirects', function () {
    $this->get('/broadcasting/login')
        ->assertRedirect(route('broadcasting.index'));

    $this->assertAuthenticatedAs($this->user);
});

test('broadcasting page requires authentication', function () {
    $this->get('/broadcasting')
        ->assertRedirect('/login');
});

test('broadcasting page renders for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/broadcasting')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Broadcasting')
            ->has('user')
            ->has('post')
        );
});

test('trigger public event dispatches event', function () {
    Event::fake();

    $this->actingAs($this->user)
        ->post('/broadcasting/public-event')
        ->assertRedirect();

    Event::assertDispatched(PublicAnnouncementMade::class);
});

test('trigger private event dispatches event', function () {
    Event::fake();

    $this->actingAs($this->user)
        ->post('/broadcasting/private-event')
        ->assertRedirect();

    Event::assertDispatched(OrderStatusUpdated::class);
});

test('trigger presence event dispatches event', function () {
    Event::fake();

    $this->actingAs($this->user)
        ->post('/broadcasting/presence-event')
        ->assertRedirect();

    Event::assertDispatched(ChatMessageSent::class);
});

test('trigger model event updates post', function () {
    $this->actingAs($this->user)
        ->post('/broadcasting/model-event')
        ->assertRedirect();

    $this->post->refresh();
    expect($this->post->title)->toStartWith('Updated at');
});

test('trigger notification sends notification', function () {
    Notification::fake();

    $this->actingAs($this->user)
        ->post('/broadcasting/notification')
        ->assertRedirect();

    Notification::assertSentTo($this->user, DemoNotification::class);
});
