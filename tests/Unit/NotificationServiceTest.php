<?php

namespace Tests\Unit;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = new NotificationService;
    }

    public function test_send_creates_notification_record()
    {
        $user = User::factory()->create();

        $notification = $this->notificationService->send(
            user: $user,
            type: 'test_notification',
            title: 'Test Title',
            message: 'Test Message',
            channel: 'in_app'
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals('test_notification', $notification->type);
        $this->assertEquals('Test Title', $notification->title);
        $this->assertEquals('Test Message', $notification->message);
    }

    public function test_send_marks_notification_as_sent_on_success()
    {
        $user = User::factory()->create();

        $notification = $this->notificationService->send(
            user: $user,
            type: 'test_notification',
            title: 'Test Title',
            message: 'Test Message',
            channel: 'in_app'
        );

        $this->assertEquals('sent', $notification->status);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_send_bulk_sends_to_multiple_users()
    {
        $users = User::factory()->count(3)->create();

        $result = $this->notificationService->sendBulk(
            users: $users,
            type: 'bulk_notification',
            title: 'Bulk Title',
            message: 'Bulk Message',
            channel: 'in_app'
        );

        $this->assertEquals(3, $result['sent']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(3, $result['sent_ids']);
    }

    public function test_send_with_reference()
    {
        $user = User::factory()->create();

        $notification = $this->notificationService->send(
            user: $user,
            type: 'order_notification',
            title: 'Order Update',
            message: 'Your order has been shipped',
            channel: 'in_app',
            data: ['order_id' => '12345'],
            referenceType: 'order',
            referenceId: '12345'
        );

        $this->assertEquals('order', $notification->reference_type);
        $this->assertEquals('12345', $notification->reference_id);
        $this->assertArrayHasKey('order_id', $notification->data);
    }

    public function test_send_stores_additional_data()
    {
        $user = User::factory()->create();

        $data = [
            'action_url' => 'https://example.com/action',
            'expires_at' => now()->addHours(24)->toIso8601String(),
        ];

        $notification = $this->notificationService->send(
            user: $user,
            type: 'action_required',
            title: 'Action Required',
            message: 'Please complete your profile',
            channel: 'in_app',
            data: $data
        );

        $this->assertEquals($data['action_url'], $notification->data['action_url']);
        $this->assertEquals($data['expires_at'], $notification->data['expires_at']);
    }

    public function test_send_dispatches_fcm_job_for_in_app_notifications_when_push_allowed(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $user = User::factory()->create();

        $notification = $this->notificationService->send(
            user: $user,
            type: 'test_notification',
            title: 'Test Title',
            message: 'Test Message',
            channel: 'in_app'
        );

        \Illuminate\Support\Facades\Queue::assertPushed(
            \App\Domain\Notification\Jobs\SendFcmPushNotificationJob::class,
            fn ($job) => $job->notificationId === $notification->id
        );
    }

    public function test_send_dispatches_fcm_job_for_email_notifications_when_push_allowed(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Mail::fake();
        $user = User::factory()->create(['email' => 'seller@example.com']);

        $notification = $this->notificationService->send(
            user: $user,
            type: 'store_approved',
            title: 'Store approved',
            message: 'Your store was approved.',
            channel: 'email'
        );

        \Illuminate\Support\Facades\Queue::assertPushed(
            \App\Domain\Notification\Jobs\SendFcmPushNotificationJob::class,
            fn ($job) => $job->notificationId === $notification->id
        );
    }

    public function test_send_does_not_dispatch_fcm_job_when_push_preference_is_disabled(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $user = User::factory()->create();
        \App\Domain\User\Models\UserPreference::factory()->create([
            'user_id' => $user->id,
            'push_notifications' => false,
        ]);

        $this->notificationService->send(
            user: $user,
            type: 'test_notification',
            title: 'Test Title',
            message: 'Test Message',
            channel: 'in_app'
        );

        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Domain\Notification\Jobs\SendFcmPushNotificationJob::class);
    }

    public function test_send_does_not_dispatch_fcm_job_for_sms_or_push_channels(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $user = User::factory()->create();

        $this->notificationService->send(
            user: $user,
            type: 'sms_test',
            title: 'SMS',
            message: 'SMS',
            channel: 'sms'
        );

        $this->notificationService->send(
            user: $user,
            type: 'push_test',
            title: 'Push',
            message: 'Push',
            channel: 'push'
        );

        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Domain\Notification\Jobs\SendFcmPushNotificationJob::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
