import { HttpClient, HttpHeaders } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root',
})
export class ProjectService {
  private http = inject(HttpClient);
  private authService = inject(AuthService);
  private apiUrl = 'http://localhost:8000/api';

  private getHeaders() {
    const token = this.authService.getToken();

    return {
      headers: new HttpHeaders({
        'Authorization': `Bearer ${token}`
      })
    }
  }

  getProjects() {
    return this.http.get(`${this.apiUrl}/projects`);
  }

  getProject(id: number) {
    return this.http.get(`${this.apiUrl}/projects/${id}`);
  }

  createProject(project: any) {
    return this.http.post(`${this.apiUrl}/projects`, project, this.getHeaders());
  }

  editProject(id: number, project: any) {
    return this.http.put(`${this.apiUrl}/projects/${id}`, project, this.getHeaders());
  }

  deleteProject(id: number) {
    return this.http.delete(`${this.apiUrl}/projects/${id}`, this.getHeaders());
  }

}
