import type { App } from 'vue';
import { setupLayouts } from 'virtual:generated-layouts';
import type { RouteRecordRaw } from 'vue-router/auto';
import { createRouter, createWebHistory } from 'vue-router/auto';
import { authGuard } from '@utils/authGuard';

function recursiveLayouts(route: RouteRecordRaw): RouteRecordRaw {
  if (route.children) {
    for (let i = 0; i < route.children.length; i++)
      route.children[i] = recursiveLayouts(route.children[i]);
    return route;
  }
  return setupLayouts([route])[0];
}

// 👉 Set requiresAuth to true for all routes by default
function addMetaToRoutes(routes: RouteRecordRaw[]): RouteRecordRaw[] {
  return routes.map(route => {
    route.meta = { ...route.meta, requiresAuth: true };

    if (route.path === 'login') {
      route.meta.requiresAuth = false;
    }

    if (route.children) {
      route.children = addMetaToRoutes(route.children);
    }
    return route;
  });
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to: { hash: any; }) {
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth', top: 60 };
    }
    return { top: 0 };
  },
  extendRoutes: (pages: any[]) => addMetaToRoutes(pages.map((route: any) => recursiveLayouts(route))),
});

// 👉 Check if route requires authentication
router.beforeEach((to: any, from: any, next: () => void) => {
  if (to.matched.some((record: { meta: { requiresAuth: any; }; }) => record.meta.requiresAuth)) {
    authGuard(to, from, next);
  } else {
    next();
  }
});

export  { router };

export default function registerPlugins(app:App) {
  app.use(router)
}
