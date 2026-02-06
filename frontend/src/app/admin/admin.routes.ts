import { Routes } from '@angular/router';

export const ADMIN_ROUTES: Routes = [
  {
    path: 'projects',
    loadChildren: () => import('./projects/projects.routes').then(m => m.PROJECTS_ROUTES)
  },
];