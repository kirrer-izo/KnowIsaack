import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home/home.component';
import { LiveYourBooksComponent } from './pages/live-your-books/live-your-books.component';
import { SoleProprietorCRMComponent } from './pages/sole-proprietor-crm/sole-proprietor-crm.component';
import { LoginComponent } from './pages/login/login.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { authGuard } from './services/auth-guard';
import { ProjectList } from './admin/projects/ui/project-list/project-list';
import { ProjectDetails } from './admin/projects/feature/project-details/project-details';
import { ProjectForm } from './admin/projects/ui/project-form/project-form';

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
            {
            path: '',
            loadChildren: () =>
                import('./admin/admin.routes')
                .then(m => m.ADMIN_ROUTES),
            }

        ]
    },


]