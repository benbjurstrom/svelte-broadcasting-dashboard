<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Events\OrderStatusUpdated;
use App\Events\PublicAnnouncementMade;
use App\Models\Post;
use App\Models\User;
use App\Notifications\DemoNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BroadcastingDemoController extends Controller
{
    public function login(): RedirectResponse
    {
        $user = User::firstOrFail();
        auth()->login($user);

        return redirect()->route('broadcasting.index');
    }

    public function switchUser(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user = User::where('id', $request->input('user_id'))->firstOrFail();
        auth()->login($user);

        return redirect()->route('broadcasting.index');
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $post = Post::where('user_id', $user->id)->firstOrFail();
        $allUsers = User::select('id', 'name')->orderBy('id')->get();

        return Inertia::render('Broadcasting', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => $post->body,
            ],
            'allUsers' => $allUsers,
        ]);
    }

    public function triggerPublicEvent(): RedirectResponse
    {
        event(new PublicAnnouncementMade(
            message: 'Hello from the server! This is a public announcement.',
            timestamp: now()->toIso8601String(),
        ));

        return back();
    }

    public function triggerPrivateEvent(Request $request): RedirectResponse
    {
        $user = $request->user();

        event(new OrderStatusUpdated(
            orderId: $user->id,
            status: collect(['Processing', 'Shipped', 'Delivered', 'Cancelled'])->random(),
            timestamp: now()->toIso8601String(),
        ));

        return back();
    }

    public function triggerPresenceEvent(Request $request): RedirectResponse
    {
        $user = $request->user();

        event(new ChatMessageSent(
            userName: $user->name,
            message: collect([
                'Hello everyone!',
                'How is it going?',
                'Great to be here!',
                'Broadcasting is awesome!',
            ])->random(),
            timestamp: now()->toIso8601String(),
        ));

        return back();
    }

    public function triggerModelEvent(Request $request): RedirectResponse
    {
        $post = Post::where('user_id', $request->user()->id)->firstOrFail();

        $post->update([
            'title' => 'Updated at '.now()->toTimeString(),
            'body' => fake()->paragraph(),
        ]);

        return back();
    }

    public function triggerNotification(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->notifyNow(new DemoNotification(
            title: 'New Notification',
            body: 'You received a broadcast notification at '.now()->toTimeString(),
        ));

        return back();
    }
}
