<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, news } from '@/routes';
import type { NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Newspaper, Globe, FileText, List, ClipboardList, Database, MessageSquare, Settings, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'News',
        href: news(),
        icon: Newspaper,
    },
    {
        title: 'Websites',
        href: { url: '/websites', method: 'get' },
        icon: Globe,
    },
    {
        title: 'Articles',
        href: { url: '/admin/articles', method: 'get' },
        icon: FileText,
    },
    {
        title: 'WP Categories',
        href: { url: '/admin/wp-categories', method: 'get' },
        icon: List,
    },
    {
        title: 'AI Logs',
        href: { url: '/admin/logs', method: 'get' },
        icon: ClipboardList,
    },
    {
        title: 'Jobs',
        href: { url: '/admin/jobs', method: 'get' },
        icon: Database,
    },
    {
        title: 'Topics',
        href: { url: '/admin/topics', method: 'get' },
        icon: MessageSquare,
    },
    {
        title: 'Instructions',
        href: { url: '/admin/instructions', method: 'get' },
        icon: Settings,
    },
    {
        title: 'Users',
        href: { url: '/admin/users', method: 'get' },
        icon: Users,
    },
];


</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
