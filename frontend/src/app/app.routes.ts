import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home/home.component';
import { LiveYourBooksComponent } from './pages/live-your-books/live-your-books.component';
import { SoleProprietorCRMComponent } from './pages/sole-proprietor-crm/sole-proprietor-crm.component';
import { LoginComponent } from './pages/login/login.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { authGuard } from './services/auth-guard';
import { AdminProjectsComponent } from './pages/admin-projects/admin-projects.component';
import { ProjectDetailsComponent } from './pages/project-details/project-details.component';

export const routes: Routes = [
    { path: '', component: HomeComponent },
    { path: 'projects/live-your-books', component: LiveYourBooksComponent },
    { path: 'projects/sole-proprietor-crm', component: SoleProprietorCRMComponent },
    { path: 'login', component: LoginComponent },
    {
        path: 'dashboard',
        component: DashboardComponent,
        canActivate: [authGuard],
        children: [
            { path: 'projects', component: AdminProjectsComponent }
        ]
    },
    { path: 'projects/:id', component: ProjectDetailsComponent}
]