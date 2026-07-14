<script setup lang="ts">
import AppIcon from '@/Components/App/AppIcon.vue';
import type { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage<PageProps>();
const current = computed(() => page.props.currentProject);
const projects = computed(() => page.props.projects || []);
const storedProjectSlug = ref<string | null>(
    typeof window === 'undefined'
        ? null
        : window.localStorage.getItem('hookroute.active-project'),
);
const activeProject = computed(
    () =>
        current.value ||
        projects.value.find(
            (project) => project.slug === storedProjectSlug.value,
        ) ||
        null,
);
const mobileOpen = ref(false);
const userMenuOpen = ref(false);
const userMenu = ref<HTMLElement | null>(null);
const eventId = computed(
    () =>
        (page.props as PageProps & { event?: { public_id?: string } }).event
            ?.public_id,
);

const nav = computed(() =>
    current.value
        ? [
              {
                  label: 'Overview',
                  icon: 'dashboard',
                  route: 'projects.dashboard',
                  pattern: 'projects.dashboard',
              },
              {
                  label: 'Sources',
                  icon: 'source',
                  route: 'projects.sources.index',
                  pattern: 'projects.sources.*',
              },
              {
                  label: 'Destinations',
                  icon: 'destination',
                  route: 'projects.destinations.index',
                  pattern: 'projects.destinations.*',
              },
              {
                  label: 'Routes',
                  icon: 'route',
                  route: 'projects.connections.index',
                  pattern: 'projects.connections.*',
              },
              {
                  label: 'Events',
                  icon: 'event',
                  route: 'projects.events.index',
                  pattern: 'projects.events.*',
              },
              {
                  label: 'Team',
                  icon: 'team',
                  route: 'projects.team.index',
                  pattern: 'projects.team.*',
              },
          ]
        : [],
);

function isActive(pattern: string) {
    return Boolean(route().current(pattern));
}

function closeUserMenu(event: PointerEvent) {
    if (!userMenu.value?.contains(event.target as Node)) {
        userMenuOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('pointerdown', closeUserMenu);
    if (current.value) {
        storedProjectSlug.value = current.value.slug;
        window.localStorage.setItem(
            'hookroute.active-project',
            current.value.slug,
        );
    }
});
onBeforeUnmount(() =>
    document.removeEventListener('pointerdown', closeUserMenu),
);
</script>

<template>
    <div class="app-shell">
        <aside class="sidebar">
            <Link :href="route('home')" class="brand"
                ><span class="brand-mark" /><span class="brand-word"
                    >hookroute</span
                ></Link
            >
            <Link :href="route('projects.index')" class="project-switch">
                <span class="project-switch-copy">
                    <label>{{
                        activeProject ? 'Active project' : 'Workspace'
                    }}</label>
                    <strong>{{
                        activeProject?.name || 'Choose a project'
                    }}</strong>
                </span>
                <AppIcon name="chevron-right" :size="17" />
            </Link>
            <nav class="nav-stack">
                <template v-for="item in nav" :key="item.route">
                    <Link
                        :href="route(item.route, current!.slug)"
                        class="nav-link"
                        :class="{ active: isActive(item.pattern) }"
                    >
                        <span class="nav-icon"
                            ><AppIcon :name="item.icon" :size="17"
                        /></span>
                        {{ item.label }}
                    </Link>
                    <Link
                        v-if="
                            item.route === 'projects.events.index' &&
                            route().current('projects.events.show')
                        "
                        :href="
                            route('projects.events.show', [
                                current!.slug,
                                eventId,
                            ])
                        "
                        class="nav-sub"
                    >
                        <span>↳</span>
                        Event detail
                    </Link>
                </template>
            </nav>
            <div class="sidebar-bottom">
                <div ref="userMenu" class="user-menu">
                    <div v-if="userMenuOpen" class="user-popover">
                        <Link :href="route('profile.edit')">
                            <AppIcon name="user" :size="16" />
                            Profile settings
                        </Link>
                        <Link :href="route('logout')" method="post" as="button">
                            <AppIcon name="logout" :size="16" />
                            Sign out
                        </Link>
                    </div>
                    <button
                        type="button"
                        class="user-card"
                        :aria-expanded="userMenuOpen"
                        @click="userMenuOpen = !userMenuOpen"
                    >
                        <span class="avatar">{{
                            page.props.auth.user.name.charAt(0).toUpperCase()
                        }}</span>
                        <span class="user-copy"
                            ><strong>{{ page.props.auth.user.name }}</strong
                            ><span>{{ page.props.auth.user.email }}</span></span
                        >
                        <AppIcon name="chevron-down" :size="15" />
                    </button>
                </div>
            </div>
        </aside>
        <main class="main">
            <header class="mobile-bar">
                <Link
                    :href="route('home')"
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
                    :class="{ active: isActive(item.pattern) }"
                    >{{ item.label }}</Link
                >
                <Link :href="route('projects.index')" class="nav-link"
                    >Projects</Link
                >
                <Link :href="route('profile.edit')" class="nav-link"
                    >Profile</Link
                >
            </nav>
            <div class="content"><slot /></div>
        </main>
        <div v-if="page.props.flash?.success" class="toast">
            {{ page.props.flash.success }}
        </div>
    </div>
</template>
