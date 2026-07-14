<script setup lang="ts">
import type { PageProps } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<PageProps>();
const current = computed(() => page.props.currentProject);
const projects = computed(() => page.props.projects || []);
const mobileOpen = ref(false);

const nav = computed(() =>
    current.value
        ? [
              { label: 'Overview', code: '01', route: 'projects.dashboard' },
              { label: 'Sources', code: '02', route: 'projects.sources.index' },
              {
                  label: 'Destinations',
                  code: '03',
                  route: 'projects.destinations.index',
              },
              {
                  label: 'Routes',
                  code: '04',
                  route: 'projects.connections.index',
              },
              { label: 'Events', code: '05', route: 'projects.events.index' },
              { label: 'Team', code: '06', route: 'projects.team.index' },
          ]
        : [],
);

function switchProject(event: Event) {
    const slug = (event.target as HTMLSelectElement).value;
    if (slug) router.visit(route('projects.dashboard', slug));
}
</script>

<template>
    <div class="app-shell">
        <aside class="sidebar">
            <Link :href="route('dashboard')" class="brand"
                ><span class="brand-mark" /><span class="brand-word"
                    >hookroute</span
                ></Link
            >
            <div class="project-switch">
                <label>Active project</label>
                <select :value="current?.slug" @change="switchProject">
                    <option
                        v-for="project in projects"
                        :key="project.id"
                        :value="project.slug"
                    >
                        {{ project.name }}
                    </option>
                </select>
            </div>
            <nav class="nav-stack">
                <Link
                    v-for="item in nav"
                    :key="item.route"
                    :href="route(item.route, current!.slug)"
                    class="nav-link"
                    :class="{ active: route().current(item.route) }"
                >
                    <span class="nav-icon">{{ item.code }}</span
                    >{{ item.label }}
                </Link>
                <Link
                    :href="route('projects.index')"
                    class="nav-link"
                    :class="{ active: route().current('projects.index') }"
                    ><span class="nav-icon">＋</span>Projects</Link
                >
            </nav>
            <div class="sidebar-bottom">
                <div class="user-card">
                    <span class="avatar">{{
                        page.props.auth.user.name.charAt(0).toUpperCase()
                    }}</span>
                    <span class="user-copy"
                        ><strong>{{ page.props.auth.user.name }}</strong
                        ><span>{{ page.props.auth.user.email }}</span></span
                    >
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="logout-link"
                        aria-label="Sign out"
                        >↗</Link
                    >
                </div>
            </div>
        </aside>
        <main class="main">
            <header class="mobile-bar">
                <Link
                    :href="route('dashboard')"
                    class="brand"
                    style="padding: 0; font-size: 18px"
                    ><span
                        class="brand-mark"
                        style="width: 22px; height: 22px"
                    />hookroute</Link
                ><button
                    class="icon-button"
                    style="color: white; border-color: #526059"
                    @click="mobileOpen = !mobileOpen"
                >
                    {{ mobileOpen ? '×' : '≡' }}
                </button>
            </header>
            <nav v-if="mobileOpen" class="mobile-nav">
                <Link
                    v-for="item in nav"
                    :key="item.route"
                    :href="route(item.route, current!.slug)"
                    class="nav-link"
                    :class="{ active: route().current(item.route) }"
                    >{{ item.label }}</Link
                >
            </nav>
            <div class="content"><slot /></div>
        </main>
        <div v-if="page.props.flash?.success" class="toast">
            {{ page.props.flash.success }}
        </div>
    </div>
</template>
