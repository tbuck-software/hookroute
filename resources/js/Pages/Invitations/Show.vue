<script setup lang="ts">
import AppShell from '@/Layouts/AppShell.vue';
import { Head, router } from '@inertiajs/vue3';
const props = defineProps<{ invitation: any }>();
function accept() {
    router.post(route('invitations.accept', props.invitation.token));
}
</script>
<template>
    <Head title="Project invitation" /><AppShell
        ><div style="max-width: 720px; margin: 8vh auto">
            <section class="panel" style="box-shadow: 12px 12px 0 var(--acid)">
                <header
                    class="panel-head"
                    style="background: var(--acid); border-color: var(--ink)"
                >
                    <span class="section-label">Project invitation</span
                    ><span class="resource-kind">{{ invitation.role }}</span>
                </header>
                <div class="panel-body" style="padding: clamp(28px, 6vw, 64px)">
                    <h1
                        style="
                            margin: 0;
                            font:
                                600 clamp(40px, 7vw, 70px)/0.95 Charter,
                                Georgia,
                                serif;
                            letter-spacing: -0.055em;
                        "
                    >
                        Join {{ invitation.project }}
                    </h1>
                    <p
                        class="muted"
                        style="
                            font-size: 16px;
                            line-height: 1.6;
                            margin: 24px 0;
                        "
                    >
                        {{ invitation.inviter }} invited
                        <strong>{{ invitation.email }}</strong> to collaborate
                        as {{ invitation.role }}.
                    </p>
                    <button class="btn btn-primary" @click="accept">
                        Accept invitation →
                    </button>
                </div>
            </section>
        </div></AppShell
    >
</template>
