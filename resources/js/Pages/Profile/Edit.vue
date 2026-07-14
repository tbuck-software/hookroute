<script setup lang="ts">
import Dialog from '@/Components/App/Dialog.vue';
import PageHeader from '@/Components/App/PageHeader.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const page = usePage<any>();
const user = page.props.auth.user;
const deleteOpen = ref(false);
const profile = useForm({ name: user.name, email: user.email });
const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const deletion = useForm({ password: '' });

function updateProfile() {
    profile.patch(route('profile.update'), { preserveScroll: true });
}

function updatePassword() {
    password.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => password.reset(),
    });
}

function deleteAccount() {
    deletion.delete(route('profile.destroy'), {
        preserveScroll: true,
    });
}

function closeDelete() {
    deleteOpen.value = false;
    deletion.clearErrors();
    deletion.reset();
}
</script>

<template>
    <Head title="Profile settings" />
    <AppShell>
        <PageHeader
            eyebrow="Your account"
            title="Profile settings"
            description="Manage the identity you use across every Hookroute project."
        />

        <div class="profile-grid">
            <section class="panel">
                <header class="panel-head">
                    <h2>Profile information</h2>
                    <span
                        v-if="profile.recentlySuccessful"
                        class="status delivered"
                        >Saved</span
                    >
                </header>
                <form class="panel-body" @submit.prevent="updateProfile">
                    <div class="form-grid">
                        <div class="field full">
                            <label for="profile-name">Name</label>
                            <input
                                id="profile-name"
                                v-model="profile.name"
                                class="input"
                                autocomplete="name"
                                required
                            />
                            <div v-if="profile.errors.name" class="field-error">
                                {{ profile.errors.name }}
                            </div>
                        </div>
                        <div class="field full">
                            <label for="profile-email">Email address</label>
                            <input
                                id="profile-email"
                                v-model="profile.email"
                                type="email"
                                class="input"
                                autocomplete="username"
                                required
                            />
                            <div
                                v-if="profile.errors.email"
                                class="field-error"
                            >
                                {{ profile.errors.email }}
                            </div>
                        </div>
                        <div
                            v-if="
                                mustVerifyEmail &&
                                user.email_verified_at === null
                            "
                            class="field full"
                        >
                            <p class="field-hint">
                                This email address is not verified.
                                <Link
                                    :href="route('verification.send')"
                                    method="post"
                                    as="button"
                                    class="btn btn-small btn-soft"
                                >
                                    Send verification email
                                </Link>
                            </p>
                            <p
                                v-if="status === 'verification-link-sent'"
                                class="field-hint"
                            >
                                A new verification link has been sent.
                            </p>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button
                            class="btn btn-primary"
                            :disabled="profile.processing"
                        >
                            Save profile
                        </button>
                    </div>
                </form>
            </section>

            <section class="panel">
                <header class="panel-head">
                    <h2>Change password</h2>
                    <span
                        v-if="password.recentlySuccessful"
                        class="status delivered"
                        >Updated</span
                    >
                </header>
                <form class="panel-body" @submit.prevent="updatePassword">
                    <div class="form-grid">
                        <div class="field full">
                            <label for="current-password"
                                >Current password</label
                            >
                            <input
                                id="current-password"
                                v-model="password.current_password"
                                type="password"
                                class="input"
                                autocomplete="current-password"
                            />
                            <div
                                v-if="password.errors.current_password"
                                class="field-error"
                            >
                                {{ password.errors.current_password }}
                            </div>
                        </div>
                        <div class="field">
                            <label for="new-password">New password</label>
                            <input
                                id="new-password"
                                v-model="password.password"
                                type="password"
                                class="input"
                                autocomplete="new-password"
                            />
                            <div
                                v-if="password.errors.password"
                                class="field-error"
                            >
                                {{ password.errors.password }}
                            </div>
                        </div>
                        <div class="field">
                            <label for="confirm-password"
                                >Confirm password</label
                            >
                            <input
                                id="confirm-password"
                                v-model="password.password_confirmation"
                                type="password"
                                class="input"
                                autocomplete="new-password"
                            />
                        </div>
                    </div>
                    <div class="form-actions">
                        <button
                            class="btn btn-primary"
                            :disabled="password.processing"
                        >
                            Update password
                        </button>
                    </div>
                </form>
            </section>

            <section class="panel danger-zone">
                <header class="panel-head"><h2>Delete account</h2></header>
                <div class="panel-body">
                    <p class="muted" style="margin-top: 0; line-height: 1.6">
                        Account deletion is permanent. Project owners must first
                        transfer ownership to another team member.
                    </p>
                    <button class="btn btn-danger" @click="deleteOpen = true">
                        Delete account
                    </button>
                </div>
            </section>
        </div>

        <Dialog
            v-if="deleteOpen"
            title="Delete your account?"
            @close="closeDelete"
        >
            <form @submit.prevent="deleteAccount">
                <p class="muted" style="margin-top: 0; line-height: 1.6">
                    Enter your password to permanently delete this account. This
                    action cannot be undone.
                </p>
                <div class="field">
                    <label for="delete-password">Password</label>
                    <input
                        id="delete-password"
                        v-model="deletion.password"
                        type="password"
                        class="input"
                        autofocus
                        autocomplete="current-password"
                    />
                    <div v-if="deletion.errors.password" class="field-error">
                        {{ deletion.errors.password }}
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" @click="closeDelete">
                        Cancel
                    </button>
                    <button
                        class="btn btn-danger"
                        :disabled="deletion.processing"
                    >
                        Delete account
                    </button>
                </div>
            </form>
        </Dialog>
    </AppShell>
</template>
