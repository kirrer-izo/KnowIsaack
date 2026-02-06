import { Routes } from '@angular/router';

export const PROJECTS_ROUTES: Routes = [
    {
        path: '',
        loadComponent: () =>
            import('./feature/project-list-page/project-list-page').then(m => m.ProjectListPage),
    },

    {
        path: 'new',
        loadComponent: () => import('./feature/project-upsert/project-upsert').then(m => m.ProjectUpsert),
    },

    {
        path: ':id',
        loadComponent: () => import('./feature/project-details/project-details').then(m => m.ProjectDetails),
    },

    {
        path: ':id/edit',
        loadComponent: () => import('./feature/project-upsert/project-upsert').then(m => m.ProjectUpsert),
    },

];