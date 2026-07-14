<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('timezone')->default('UTC');
            $table->unsignedSmallInteger('event_retention_days')->default(30);
            $table->timestamps();
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();
            $table->primary(['project_id', 'user_id']);
        });

        Schema::create('project_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default('member');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'email']);
        });

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('public_id', 26)->unique();
            $table->string('name');
            $table->string('slug');
            $table->text('secret');
            $table->string('secret_hash', 64);
            $table->text('signing_secret')->nullable();
            $table->string('signature_header')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_received_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'slug']);
        });

        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->longText('config');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('filters')->nullable();
            $table->string('payload_mode')->default('passthrough');
            $table->text('subject_template')->nullable();
            $table->longText('body_template')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->index(['source_id', 'enabled']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('public_id', 26)->unique();
            $table->string('method', 12)->default('POST');
            $table->string('content_type')->nullable();
            $table->json('headers')->nullable();
            $table->longText('raw_body')->nullable();
            $table->json('payload')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamps();
            $table->unique(['source_id', 'idempotency_key']);
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'connection_id']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('digest_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->timestamp('window_start');
            $table->timestamp('window_end');
            $table->json('event_ids')->nullable();
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedInteger('total_event_count')->default(0);
            $table->boolean('truncated')->default(false);
            $table->string('status')->default('pending');
            $table->timestamp('processing_started_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['destination_id', 'window_start', 'window_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digest_runs');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('events');
        Schema::dropIfExists('connections');
        Schema::dropIfExists('destinations');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('project_invitations');
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');
    }
};
