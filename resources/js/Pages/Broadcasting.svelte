<script>
    import { router } from '@inertiajs/svelte';
    import {
        createConnectionStatus,
        createEcho,
        createEchoPublic,
        createEchoPresence,
        createEchoModel,
        createEchoNotification,
    } from '@laravel/echo-svelte';
    import { untrack } from 'svelte';

    let { user, post } = $props();

    const userId = untrack(() => user.id);
    const postId = untrack(() => post.id);

    // 1. Connection Status
    const connectionStatus = createConnectionStatus();
    const status = $derived(connectionStatus());

    // Channel state tracking
    let channelState = $state({
        public: { listening: true, left: false },
        private: { listening: true, left: false },
        presence: { listening: true, left: false },
        model: { listening: true, left: false },
        notification: { listening: true, left: false },
    });

    // 2. Public Channel
    let publicEvents = $state([]);
    const publicChannel = createEchoPublic('announcements', 'PublicAnnouncementMade', (e) => {
        publicEvents = [...publicEvents, { message: e.message, timestamp: e.timestamp }];
    });

    // 3. Private Channel
    let privateEvents = $state([]);
    const privateChannel = createEcho(`orders.${userId}`, 'OrderStatusUpdated', (e) => {
        privateEvents = [...privateEvents, { orderId: e.orderId, status: e.status, timestamp: e.timestamp }];
    });

    // 4. Presence Channel
    let presenceMessages = $state([]);
    let presenceUsers = $state([]);
    const presenceChannel = createEchoPresence('chat-room', 'ChatMessageSent', (e) => {
        presenceMessages = [...presenceMessages, { userName: e.userName, message: e.message, timestamp: e.timestamp }];
    });

    presenceChannel.channel()
        .here((users) => { presenceUsers = users; })
        .joining((u) => { presenceUsers = [...presenceUsers, u]; })
        .leaving((u) => { presenceUsers = presenceUsers.filter(p => p.id !== u.id); });

    // 5. Model Events
    let modelEvents = $state([]);
    const modelChannel = createEchoModel('App.Models.Post', postId, ['PostUpdated'], (e) => {
        modelEvents = [...modelEvents, {
            model: e.model,
            timestamp: new Date().toISOString(),
        }];
    });

    // 6. Notifications
    let notifications = $state([]);
    const notificationChannel = createEchoNotification(`App.Models.User.${userId}`, (notification) => {
        notifications = [...notifications, {
            id: notification.id,
            type: notification.type,
            title: notification.title,
            body: notification.body,
            timestamp: notification.timestamp,
        }];
    });

    // Channel control helpers
    function toggleListening(key, channel) {
        if (channelState[key].left) return;
        if (channelState[key].listening) {
            channel.stopListening();
            channelState[key].listening = false;
        } else {
            channel.listen();
            channelState[key].listening = true;
        }
    }

    function doLeaveChannel(key, channel) {
        if (channelState[key].left) return;
        channel.leaveChannel();
        channelState[key].left = true;
        channelState[key].listening = false;
    }

    function doLeave(key, channel) {
        if (channelState[key].left) return;
        channel.leave();
        channelState[key].left = true;
        channelState[key].listening = false;
    }

    // Trigger helpers
    let loadingState = $state({});
    function trigger(route) {
        loadingState[route] = true;
        router.post(route, {}, {
            preserveScroll: true,
            onFinish: () => { loadingState[route] = false; },
        });
    }

    function statusColor(status) {
        const colors = {
            connected: 'bg-green-500',
            connecting: 'bg-yellow-500',
            reconnecting: 'bg-amber-500',
            disconnected: 'bg-gray-400',
            failed: 'bg-red-500',
        };
        return colors[status] ?? 'bg-gray-400';
    }

    function formatTime(timestamp) {
        return new Date(timestamp).toLocaleTimeString();
    }
</script>

<svelte:head>
    <title>Broadcasting Demo</title>
</svelte:head>

<div class="min-h-screen bg-gray-50 px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Broadcasting Demo</h1>
            <p class="mt-1 text-sm text-gray-500">Logged in as {user.name} &middot; All echo-svelte helpers in action</p>
        </div>

        <!-- 1. Connection Status -->
        <section class="mb-5 rounded-lg border border-gray-200 bg-white p-5">
            <div class="mb-3 flex items-center gap-2">
                <code class="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">createConnectionStatus()</code>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="inline-block h-2.5 w-2.5 rounded-full {statusColor(status)}"></span>
                <span class="text-sm font-medium capitalize text-gray-700">{status}</span>
            </div>
        </section>

        <!-- 2. Public Channel -->
        <section class="mb-5 rounded-lg border border-blue-200 bg-white p-5">
            <div class="mb-1 flex items-center gap-2">
                <code class="rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">createEchoPublic()</code>
            </div>
            <p class="mb-3 text-xs text-gray-500">Channel: <code>announcements</code> &middot; Event: <code>PublicAnnouncementMade</code></p>
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <button
                    onclick={() => trigger('/broadcasting/public-event')}
                    disabled={loadingState['/broadcasting/public-event']}
                    class="rounded-md bg-blue-600 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    {loadingState['/broadcasting/public-event'] ? 'Sending...' : 'Send Public Event'}
                </button>
                <button
                    onclick={() => toggleListening('public', publicChannel)}
                    disabled={channelState.public.left}
                    class="rounded-md border border-blue-300 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 disabled:opacity-40"
                >
                    {channelState.public.listening ? 'Stop Listening' : 'Listen'}
                </button>
                <button
                    onclick={() => doLeaveChannel('public', publicChannel)}
                    disabled={channelState.public.left}
                    class="rounded-md border border-blue-300 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 disabled:opacity-40"
                >
                    Leave Channel
                </button>
                <button
                    onclick={() => doLeave('public', publicChannel)}
                    disabled={channelState.public.left}
                    class="rounded-md border border-blue-300 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 disabled:opacity-40"
                >
                    Leave All
                </button>
                {#if channelState.public.left}
                    <span class="text-xs font-medium text-red-500">Left</span>
                {:else if !channelState.public.listening}
                    <span class="text-xs font-medium text-amber-500">Paused</span>
                {/if}
            </div>
            <div class="max-h-40 space-y-1.5 overflow-y-auto">
                {#each publicEvents as event, i (i)}
                    <div class="rounded bg-blue-50 px-3 py-2 text-sm text-blue-900">
                        {event.message}
                        <span class="ml-1 text-xs text-blue-400">{formatTime(event.timestamp)}</span>
                    </div>
                {/each}
                {#if publicEvents.length === 0}
                    <p class="text-xs italic text-gray-400">No events yet.</p>
                {/if}
            </div>
        </section>

        <!-- 3. Private Channel -->
        <section class="mb-5 rounded-lg border border-purple-200 bg-white p-5">
            <div class="mb-1 flex items-center gap-2">
                <code class="rounded bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700">createEcho()</code>
                <span class="rounded bg-purple-100 px-1.5 py-0.5 text-xs text-purple-600">private</span>
            </div>
            <p class="mb-3 text-xs text-gray-500">Channel: <code>orders.{user.id}</code> &middot; Event: <code>OrderStatusUpdated</code></p>
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <button
                    onclick={() => trigger('/broadcasting/private-event')}
                    disabled={loadingState['/broadcasting/private-event']}
                    class="rounded-md bg-purple-600 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50"
                >
                    {loadingState['/broadcasting/private-event'] ? 'Sending...' : 'Send Private Event'}
                </button>
                <button
                    onclick={() => toggleListening('private', privateChannel)}
                    disabled={channelState.private.left}
                    class="rounded-md border border-purple-300 px-2.5 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-50 disabled:opacity-40"
                >
                    {channelState.private.listening ? 'Stop Listening' : 'Listen'}
                </button>
                <button
                    onclick={() => doLeaveChannel('private', privateChannel)}
                    disabled={channelState.private.left}
                    class="rounded-md border border-purple-300 px-2.5 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-50 disabled:opacity-40"
                >
                    Leave Channel
                </button>
                <button
                    onclick={() => doLeave('private', privateChannel)}
                    disabled={channelState.private.left}
                    class="rounded-md border border-purple-300 px-2.5 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-50 disabled:opacity-40"
                >
                    Leave All
                </button>
                {#if channelState.private.left}
                    <span class="text-xs font-medium text-red-500">Left</span>
                {:else if !channelState.private.listening}
                    <span class="text-xs font-medium text-amber-500">Paused</span>
                {/if}
            </div>
            <div class="max-h-40 space-y-1.5 overflow-y-auto">
                {#each privateEvents as event, i (i)}
                    <div class="rounded bg-purple-50 px-3 py-2 text-sm text-purple-900">
                        Order #{event.orderId} &rarr; <span class="font-medium">{event.status}</span>
                        <span class="ml-1 text-xs text-purple-400">{formatTime(event.timestamp)}</span>
                    </div>
                {/each}
                {#if privateEvents.length === 0}
                    <p class="text-xs italic text-gray-400">No events yet.</p>
                {/if}
            </div>
        </section>

        <!-- 4. Presence Channel -->
        <section class="mb-5 rounded-lg border border-green-200 bg-white p-5">
            <div class="mb-1 flex items-center gap-2">
                <code class="rounded bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">createEchoPresence()</code>
                <span class="rounded bg-green-100 px-1.5 py-0.5 text-xs text-green-600">presence</span>
            </div>
            <p class="mb-3 text-xs text-gray-500">Channel: <code>chat-room</code> &middot; Event: <code>ChatMessageSent</code></p>

            <div class="mb-3 flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-medium text-gray-500">Online:</span>
                {#each presenceUsers as u (u.id)}
                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">{u.name}</span>
                {/each}
                {#if presenceUsers.length === 0}
                    <span class="text-xs italic text-gray-400">No users</span>
                {/if}
            </div>

            <div class="mb-3 flex flex-wrap items-center gap-2">
                <button
                    onclick={() => trigger('/broadcasting/presence-event')}
                    disabled={loadingState['/broadcasting/presence-event']}
                    class="rounded-md bg-green-600 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                >
                    {loadingState['/broadcasting/presence-event'] ? 'Sending...' : 'Send Chat Message'}
                </button>
                <button
                    onclick={() => toggleListening('presence', presenceChannel)}
                    disabled={channelState.presence.left}
                    class="rounded-md border border-green-300 px-2.5 py-1.5 text-xs font-medium text-green-700 hover:bg-green-50 disabled:opacity-40"
                >
                    {channelState.presence.listening ? 'Stop Listening' : 'Listen'}
                </button>
                <button
                    onclick={() => doLeaveChannel('presence', presenceChannel)}
                    disabled={channelState.presence.left}
                    class="rounded-md border border-green-300 px-2.5 py-1.5 text-xs font-medium text-green-700 hover:bg-green-50 disabled:opacity-40"
                >
                    Leave Channel
                </button>
                <button
                    onclick={() => doLeave('presence', presenceChannel)}
                    disabled={channelState.presence.left}
                    class="rounded-md border border-green-300 px-2.5 py-1.5 text-xs font-medium text-green-700 hover:bg-green-50 disabled:opacity-40"
                >
                    Leave All
                </button>
                {#if channelState.presence.left}
                    <span class="text-xs font-medium text-red-500">Left</span>
                {:else if !channelState.presence.listening}
                    <span class="text-xs font-medium text-amber-500">Paused</span>
                {/if}
            </div>
            <div class="max-h-40 space-y-1.5 overflow-y-auto">
                {#each presenceMessages as msg, i (i)}
                    <div class="rounded bg-green-50 px-3 py-2 text-sm text-green-900">
                        <span class="font-medium">{msg.userName}:</span> {msg.message}
                        <span class="ml-1 text-xs text-green-400">{formatTime(msg.timestamp)}</span>
                    </div>
                {/each}
                {#if presenceMessages.length === 0}
                    <p class="text-xs italic text-gray-400">No messages yet.</p>
                {/if}
            </div>
        </section>

        <!-- 5. Model Events -->
        <section class="mb-5 rounded-lg border border-amber-200 bg-white p-5">
            <div class="mb-1 flex items-center gap-2">
                <code class="rounded bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">createEchoModel()</code>
            </div>
            <p class="mb-3 text-xs text-gray-500">Model: <code>App.Models.Post.{post.id}</code> &middot; Events: <code>PostUpdated</code></p>
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <button
                    onclick={() => trigger('/broadcasting/model-event')}
                    disabled={loadingState['/broadcasting/model-event']}
                    class="rounded-md bg-amber-600 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50"
                >
                    {loadingState['/broadcasting/model-event'] ? 'Updating...' : 'Update Post'}
                </button>
                <button
                    onclick={() => toggleListening('model', modelChannel)}
                    disabled={channelState.model.left}
                    class="rounded-md border border-amber-300 px-2.5 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50 disabled:opacity-40"
                >
                    {channelState.model.listening ? 'Stop Listening' : 'Listen'}
                </button>
                <button
                    onclick={() => doLeaveChannel('model', modelChannel)}
                    disabled={channelState.model.left}
                    class="rounded-md border border-amber-300 px-2.5 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50 disabled:opacity-40"
                >
                    Leave Channel
                </button>
                <button
                    onclick={() => doLeave('model', modelChannel)}
                    disabled={channelState.model.left}
                    class="rounded-md border border-amber-300 px-2.5 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50 disabled:opacity-40"
                >
                    Leave All
                </button>
                {#if channelState.model.left}
                    <span class="text-xs font-medium text-red-500">Left</span>
                {:else if !channelState.model.listening}
                    <span class="text-xs font-medium text-amber-500">Paused</span>
                {/if}
            </div>
            <div class="max-h-40 space-y-1.5 overflow-y-auto">
                {#each modelEvents as event, i (i)}
                    <div class="rounded bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        Post updated &rarr; <span class="font-medium">"{event.model.title}"</span>
                        <span class="ml-1 text-xs text-amber-400">{formatTime(event.timestamp)}</span>
                    </div>
                {/each}
                {#if modelEvents.length === 0}
                    <p class="text-xs italic text-gray-400">No model events yet.</p>
                {/if}
            </div>
        </section>

        <!-- 6. Notifications -->
        <section class="mb-5 rounded-lg border border-rose-200 bg-white p-5">
            <div class="mb-1 flex items-center gap-2">
                <code class="rounded bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">createEchoNotification()</code>
            </div>
            <p class="mb-3 text-xs text-gray-500">Channel: <code>App.Models.User.{user.id}</code></p>
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <button
                    onclick={() => trigger('/broadcasting/notification')}
                    disabled={loadingState['/broadcasting/notification']}
                    class="rounded-md bg-rose-600 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50"
                >
                    {loadingState['/broadcasting/notification'] ? 'Sending...' : 'Send Notification'}
                </button>
                <button
                    onclick={() => toggleListening('notification', notificationChannel)}
                    disabled={channelState.notification.left}
                    class="rounded-md border border-rose-300 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-40"
                >
                    {channelState.notification.listening ? 'Stop Listening' : 'Listen'}
                </button>
                <button
                    onclick={() => doLeaveChannel('notification', notificationChannel)}
                    disabled={channelState.notification.left}
                    class="rounded-md border border-rose-300 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-40"
                >
                    Leave Channel
                </button>
                <button
                    onclick={() => doLeave('notification', notificationChannel)}
                    disabled={channelState.notification.left}
                    class="rounded-md border border-rose-300 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-40"
                >
                    Leave All
                </button>
                {#if channelState.notification.left}
                    <span class="text-xs font-medium text-red-500">Left</span>
                {:else if !channelState.notification.listening}
                    <span class="text-xs font-medium text-amber-500">Paused</span>
                {/if}
            </div>
            <div class="max-h-40 space-y-1.5 overflow-y-auto">
                {#each notifications as notif (notif.id)}
                    <div class="rounded bg-rose-50 px-3 py-2 text-sm text-rose-900">
                        <span class="font-medium">{notif.title}</span> &mdash; {notif.body}
                        <span class="ml-1 text-xs text-rose-400">{formatTime(notif.timestamp)}</span>
                    </div>
                {/each}
                {#if notifications.length === 0}
                    <p class="text-xs italic text-gray-400">No notifications yet.</p>
                {/if}
            </div>
        </section>
    </div>
</div>
