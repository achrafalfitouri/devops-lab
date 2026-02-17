<script lang="ts" setup>
import type { Component } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VNodeRenderer } from './VNodeRenderer'
import { layoutConfig } from '@layouts'
import { VerticalNavGroup, VerticalNavLink, VerticalNavSectionTitle } from '@layouts/components'
import { useLayoutConfigStore } from '@layouts/stores/config'
import { injectionKeyIsVerticalNavHovered } from '@layouts/symbols'
import type { NavGroup, NavLink, NavSectionTitle, VerticalNavItems } from '@layouts/types'

interface Props {
  tag?: string | Component
  navItems: VerticalNavItems
  isOverlayNavActive: boolean
  toggleIsOverlayNavActive: (value: boolean) => void
}

const props = withDefaults(defineProps<Props>(), {
  tag: 'aside',
})

const refNav = ref()

const isHovered = useElementHover(refNav)

provide(injectionKeyIsVerticalNavHovered, isHovered)

const configStore = useLayoutConfigStore()

const resolveNavItemComponent = (item: NavLink | NavSectionTitle | NavGroup): unknown => {
  if ('heading' in item)
    return VerticalNavSectionTitle
  if ('children' in item)
    return VerticalNavGroup
  return VerticalNavLink
}

/*
  ℹ️ Close overlay side when route is changed
  Close overlay vertical nav when link is clicked
*/
const route = useRoute()

watch(() => route.name, () => {
  props.toggleIsOverlayNavActive(false)
})

const isVerticalNavScrolled = ref(false)
const updateIsVerticalNavScrolled = (val: boolean) => isVerticalNavScrolled.value = val

const handleNavScroll = (evt: Event) => {
  isVerticalNavScrolled.value = (evt.target as HTMLElement).scrollTop > 0
}

const hideTitleAndIcon = configStore.isVerticalNavMini(isHovered)

</script>

<template>
  <Component :is="props.tag" ref="refNav" class="layout-vertical-nav" :class="[
    {
      'overlay-nav': configStore.isLessThanOverlayNavBreakpoint,
      'hovered': isHovered,
      'visible': isOverlayNavActive,
      'scrolled': isVerticalNavScrolled,
    },
  ]">
    <!-- 👉 Header -->
    <div class="nav-header">
      <slot name="nav-header">
        <RouterLink to="/" class="app-logo app-title-wrapper">
          <VNodeRenderer :nodes="layoutConfig.app.logo" />


        </RouterLink>

        <!-- 👉 Vertical nav actions -->
        <!-- Show toggle collapsible in >md and close button in <md -->

        <Component :is="layoutConfig.app.iconRenderer || 'div'" class="header-action d-lg-none"
          v-bind="layoutConfig.icons.close" @click="toggleIsOverlayNavActive(false)" />
        <!-- Button with icon tabler-menu-2 for media queries -->
        <Component :is="layoutConfig.app.iconRenderer || 'div'" class="header-action d-lg-none"
          @click="configStore.isVerticalNavCollapsed = !configStore.isVerticalNavCollapsed" />
      </slot>
    </div>
    <slot name="before-nav-items">
      <div class="vertical-nav-items-shadow" />
    </slot>
    <slot name="nav-items" :update-is-vertical-nav-scrolled="updateIsVerticalNavScrolled">
      <PerfectScrollbar :key="configStore.isAppRTL" tag="ul" class="nav-items" :options="{ wheelPropagation: false }"
        @ps-scroll-y="handleNavScroll">
        <Component :is="resolveNavItemComponent(item)" v-for="(item, index) in navItems" :key="index" :item="item"
           @click="item.onClick && item.onClick()" />

        <!-- Logout Button at the Bottom -->
        <!-- <button class="nav-item logout-button" @click="handleLogout">
          <i class="tabler-logout"></i>
          <span>Se déconnecter</span>
        </button> -->
         <!-- Version Number at the Bottom -->
        
      </PerfectScrollbar>
        
    </slot>

  </Component>
</template>

<style lang="scss" scoped>
.app-logo {
  display: flex;
  align-items: center;
  justify-content: center; // Center the logo horizontally within its parent
  /* stylelint-disable-next-line liberty/use-logical-spec */
  margin: 0 auto;
  column-gap: 0.75rem;
  height: 70px;
}
</style>


<style lang="scss">
@use "@configured-variables" as variables;
@use "@layouts/styles/mixins";

// 👉 Vertical Nav
.layout-vertical-nav {
  position: fixed;
  z-index: variables.$layout-vertical-nav-z-index;
  display: flex;
  flex-direction: column;
  block-size: 100%;
  inline-size: variables.$layout-vertical-nav-width !important;
  inset-block-start: 0;
  inset-inline-start: 0;
  transition: inline-size 0.25s ease-in-out, box-shadow 0.25s ease-in-out;
  will-change: transform, inline-size;

  .nav-header {
    display: flex;
    align-items: center;

    .header-action {
      cursor: pointer;

      @at-root {
        #{variables.$selector-vertical-nav-mini} .nav-header .header-action {

          &.nav-pin,
          &.nav-unpin {
            display: none !important;
          }
        }
      }
    }
  }

  .app-title-wrapper {
    margin-inline-end: auto;
  }

  .nav-items {
    block-size: 100%;

    // ℹ️ We no loner needs this overflow styles as perfect scrollbar applies it
    // overflow-x: hidden;

    // // ℹ️ We used `overflow-y` instead of `overflow` to mitigate overflow x. Revert back if any issue found.
    // overflow-y: auto;
  }

  .nav-item-title {
    overflow: hidden;
    margin-inline-end: auto;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  // 👉 Collapsed
  .layout-vertical-nav-collapsed & {
    &:not(.hovered) {
      inline-size: variables.$layout-vertical-nav-collapsed-width;
    }
  }
}

.logout-button {
  display: flex;
  align-items: center;
  border: none;
  background: none;
  color: #2f2b3de6;
  cursor: pointer;
  font-size: 1rem;
  margin-left: 10px;
  padding: 0.4381rem 0.5rem;
  transition: background-color 0.2s;
  white-space: nowrap;
  width: 236px;
}

.logout-button:hover {
  border-radius: 6px;
  background-color: #f4f2f2;
}

.logout-button i {
  font-size: 1.4rem;
  margin-left: 0.4rem;
}

.logout-button span {
  margin-left: 0.4rem;
  /* stylelint-disable-next-line comment-empty-line-before */
  /* Adjust margin between icon and text */
}

// Small screen vertical nav transition
@media (max-width: 1279px) {
  .layout-vertical-nav {
    &:not(.visible) {
      transform: translateX(-#{variables.$layout-vertical-nav-width});

      @include mixins.rtl {
        transform: translateX(variables.$layout-vertical-nav-width);
      }
    }

    transition: transform 0.25s ease-in-out;
  }
}


</style>
