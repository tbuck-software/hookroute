<script setup lang="ts">
import AppSelect from '@/Components/App/AppSelect.vue';
import Dialog from '@/Components/App/Dialog.vue';
import PageHeader from '@/Components/App/PageHeader.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { browserTimezone, timezoneOptions } from '@/lib/timezones';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    projects: Array<{
        id: number;
        name: string;
        slug: string;
        timezone: string;
        sources_count: number;
        destinations_count: number;
        events_count: number;
        pivot: { role: string };
    }>;
}>();
const open = ref(false);
const form = useForm({
    name: '',
    timezone: browserTimezone(),
});
const timezones = timezoneOptions();
function submit() {
    form.post(route('projects.store'), {
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>
<template>
    <Head title="Projects" /><AppShell>
        <PageHeader
            eyebrow="Workspaces"
            title="Projects"
            description="Isolate credentials and event history, then share access with exactly the people who need it."
            ><button class="btn btn-primary" @click="open = true">
                ＋ New project
            </button></PageHeader
        >
        <div v-if="projects.length" class="resource-grid">
            <Link
                v-for="project in projects"
                :key="project.id"
                :href="route('projects.dashboard', project.slug)"
                class="resource-card"
            >
                <span class="resource-kind">{{ project.pivot.role }}</span>
                <h3>{{ project.name }}</h3>
                <p class="muted mono">{{ project.timezone }}</p>
                <div class="resource-meta">
                    <span>{{ project.events_count }} events</span
                    ><span
                        >{{ project.sources_count }} →
                        {{ project.destinations_count }}</span
                    >
                </div>
            </Link>
        </div>
        <div v-else class="empty">
            <div class="empty-mark">＋</div>
            <h3>No project yet</h3>
            <p>
                Projects hold sources, destinations, routes and a shared team.
            </p>
            <button class="btn btn-primary" @click="open = true">
                Create project
            </button>
        </div>
        <Dialog v-if="open" title="Create a project" @close="open = false"
            ><form @submit.prevent="submit">
                <div class="form-grid">
                    <div class="field full">
                        <label>Name</label
                        ><input
                            v-model="form.name"
                            class="input"
                            autofocus
                            placeholder="Production operations"
                        />
                        <div v-if="form.errors.name" class="field-error">
                            {{ form.errors.name }}
                        </div>
                    </div>
                    <div class="field full">
                        <label>Timezone</label
                        ><AppSelect
                            v-model="form.timezone"
                            :options="timezones"
                            searchable
                        />
                        <div v-if="form.errors.timezone" class="field-error">
                            {{ form.errors.timezone }}
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" @click="open = false">
                        Cancel</button
                    ><button
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        Create project
                    </button>
                </div>
            </form></Dialog
        >
    </AppShell>
</template>
