import { Component, inject } from '@angular/core';
import { AuthService } from '../../services/auth.service';
import { Router, RouterModule } from '@angular/router';
import { ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-dashboard',
  imports: [ReactiveFormsModule, CommonModule, RouterModule],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class DashboardComponent {

  private authservice = inject(AuthService);
  private router = inject(Router);

  logout() {
    this.authservice.logout();

    this.router.navigate(['/login']);
  }
 
}
