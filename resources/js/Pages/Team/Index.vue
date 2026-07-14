<script setup lang="ts">
import AppSelect from '@/Components/App/AppSelect.vue';
import ChoiceCards from '@/Components/App/ChoiceCards.vue';
import Dialog from '@/Components/App/Dialog.vue';
import PageHeader from '@/Components/App/PageHeader.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { formatDate } from '@/lib/format';
import { timezoneOptions } from '@/lib/timezones';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
const props = defineProps<{
    project: any;
    members: Array<any>;
    invitations: Array<any>;
}>();
const page = usePage<any>();
const inviteOpen = ref(false);
const settingsOpen = ref(false);
const invite = useForm({ email: '', role: 'member' });
const settings = useForm({
    name: props.project.name,
    timezone: props.project.timezone,
    event_retention_days: props.project.event_retention_days,
});
const roles = [
    {
        value: 'member',
        label: 'Member',
        description: 'Inspect events and delivery status',
        icon: 'user',
    },
    {
        value: 'admin',
        label: 'Administrator',
        description: 'Manage routing and team access',
        icon: 'shield',
    },
];
const timezones = timezoneOptions();
const retentionOptions = [1, 7, 14, 30, 60, 90].map((days) => ({
    value: days,
    label: `${days} ${days === 1 ? 'day' : 'days'}`,
    icon: 'clock',
}));
function sendInvite() {
    invite.post(route('projects.team.invite', props.project.slug), {
        onSuccess: () => {
            inviteOpen.value = false;
            invite.reset();
        },
    });
}
function setRole(m: any, role: string) {
    router.patch(
        route('projects.team.update', [props.project.slug, m.id]),
        { role },
        { preserveScroll: true },
    );
}
function remove(m: any) {
    if (confirm(`Remove ${m.name} from this project?`))
        router.delete(
            route('projects.team.remove', [props.project.slug, m.id]),
        );
}
function transferOwnership(m: any) {
    if (
        confirm(
            `Transfer ownership of ${props.project.name} to ${m.name}? You will become an administrator.`,
        )
    )
        router.post(
            route('projects.team.transfer-owner', [props.project.slug, m.id]),
        );
}
function saveSettings() {
    settings.patch(route('projects.update', props.project.slug), {
        onSuccess: () => (settingsOpen.value = false),
    });
}
function deleteProject() {
    if (
        confirm(
            `Permanently delete ${props.project.name}, including every captured event?`,
        )
    )
        router.delete(route('projects.destroy', props.project.slug));
}
</script>
<template>
    <Head title="Team & settings" /><AppShell
        ><PageHeader
            eyebrow="Access control"
            title="Team & settings"
            description="Share a project without sharing an account. Administrators manage routing; members can inspect operations."
            ><button
                v-if="page.props.currentProject.can_manage"
                class="btn btn-soft"
                @click="settingsOpen = true"
            >
                Project settings</button
            ><button
                v-if="page.props.currentProject.can_manage"
                class="btn btn-primary"
                @click="inviteOpen = true"
            >
                ＋ Invite member
            </button></PageHeader
        >
        <div class="dashboard-grid">
            <section class="panel">
                <header class="panel-head">
                    <h2>Project members</h2>
                    <span class="section-label"
                        >{{ members.length }} people</span
                    >
                </header>
                <div class="data-table-wrap" style="border: 0">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Person</th>
                                <th>Role</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in members" :key="m.id">
                                <td>
                                    <strong>{{ m.name }}</strong>
                                    <div class="muted">{{ m.email }}</div>
                                </td>
                                <td>
                                    <span class="resource-kind">{{
                                        m.role
                                    }}</span>
                                </td>
                                <td>
                                    <div
                                        v-if="
                                            page.props.currentProject
                                                .can_manage &&
                                            m.role !== 'owner'
                                        "
                                        class="table-actions"
                                    >
                                        <AppSelect
                                            :model-value="m.role"
                                            :options="roles"
                                            compact
                                            style="width: 140px"
                                            @update:model-value="
                                                setRole(m, String($event))
                                            "
                                        /><button
                                            class="btn btn-small btn-danger"
                                            @click="remove(m)"
                                        >
                                            Remove
                                        </button>
                                        <button
                                            v-if="
                                                page.props.currentProject
                                                    .is_owner
                                            "
                                            class="btn btn-small btn-soft"
                                            @click="transferOwnership(m)"
                                        >
                                            Make owner
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <aside v-if="page.props.currentProject.can_manage" class="stack">
                <div class="panel">
                    <header class="panel-head">
                        <h2>Pending invitations</h2>
                    </header>
                    <div v-if="invitations.length" class="event-list">
                        <div
                            v-for="i in invitations"
                            :key="i.id"
                            style="
                                padding: 15px 18px;
                                border-bottom: 1px solid var(--line);
                            "
                        >
                            <strong style="font-size: 12px">{{
                                i.email
                            }}</strong>
                            <div class="mono muted">
                                {{ i.role }} · expires
                                {{ formatDate(i.expires_at) }}
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="panel-body muted"
                        style="font-size: 12px"
                    >
                        No outstanding invitations.
                    </div>
                </div>
            </aside>
        </div>
        <Dialog
            v-if="inviteOpen"
            title="Invite a member"
            @close="inviteOpen = false"
            ><form @submit.prevent="sendInvite">
                <div class="form-grid">
                    <div class="field full">
                        <label>Email address</label
                        ><input
                            v-model="invite.email"
                            type="email"
                            class="input"
                            autofocus
                            placeholder="person@example.com"
                        />
                        <div v-if="invite.errors.email" class="field-error">
                            {{ invite.errors.email }}
                        </div>
                    </div>
                    <div class="field full">
                        <label>Role</label
                        ><ChoiceCards v-model="invite.role" :options="roles" />
                    </div>
                </div>
                <div class="form-actions">
                    <button
                        type="button"
                        class="btn"
                        @click="inviteOpen = false"
                    >
                        Cancel</button
                    ><button
                        class="btn btn-primary"
                        :disabled="invite.processing"
                    >
                        Send invitation
                    </button>
                </div>
            </form></Dialog
        >
        <Dialog
            v-if="settingsOpen"
            title="Project settings"
            @close="settingsOpen = false"
            ><form @submit.prevent="saveSettings">
                <div class="form-grid">
                    <div class="field full">
                        <label>Name</label
                        ><input v-model="settings.name" class="input" />
                    </div>
                    <div class="field">
                        <label>Timezone</label
                        ><AppSelect
                            v-model="settings.timezone"
                            :options="timezones"
                            searchable
                        />
                    </div>
                    <div class="field">
                        <label>Event retention</label
                        ><AppSelect
                            v-model="settings.event_retention_days"
                            :options="retentionOptions"
                        />
                    </div>
                    <div
                        v-for="(error, field) in settings.errors"
                        :key="field"
                        class="field-error full"
                    >
                        {{ error }}
                    </div>
                </div>
                <div
                    class="form-actions"
                    style="justify-content: space-between"
                >
                    <button
                        v-if="page.props.currentProject.is_owner"
                        type="button"
                        class="btn btn-danger"
                        @click="deleteProject"
                    >
                        Delete project</button
                    ><span class="page-actions"
                        ><button
                            type="button"
                            class="btn"
                            @click="settingsOpen = false"
                        >
                            Cancel</button
                        ><button
                            class="btn btn-primary"
                            :disabled="settings.processing"
                        >
                            Save settings
                        </button></span
                    >
                </div>
            </form></Dialog
        >
    </AppShell>
</template>
