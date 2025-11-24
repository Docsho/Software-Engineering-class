// js/api.js
const API_BASE = 'http://localhost/gate_api'; // change if needed

async function apiFetch(path, options = {}) {
  const token = localStorage.getItem('iugb_token');
  const headers = options.headers || {};
  headers['Content-Type'] = headers['Content-Type'] || 'application/json';
  if (token) headers['Authorization'] = 'Bearer ' + token;

  const res = await fetch(API_BASE + path, { ...options, headers });
  const text = await res.text();
  let data = null;
  try { data = text ? JSON.parse(text) : null; } catch (e) { throw new Error('Invalid JSON from server'); }

  if (!res.ok) {
    // if auth error, force logout
    if (res.status === 401 || (data && data.error && /token|authorization|forbidden/i.test(data.error))) {
      // clear and redirect
      localStorage.removeItem('iugb_token');
      localStorage.removeItem('iugb_user');
      window.location.href = 'index.html';
      throw new Error(data.error || 'Unauthorized');
    }
    throw new Error(data.error || JSON.stringify(data));
  }
  return data;
}

async function apiGet(path) {
  return apiFetch(path, { method: 'GET' });
}
async function apiPost(path, body) {
  return apiFetch(path, { method: 'POST', body: JSON.stringify(body) });
}
async function apiPut(path, body) {
  return apiFetch(path, { method: 'PUT', body: JSON.stringify(body) });
}
async function apiDelete(path, body) {
  return apiFetch(path, { method: 'DELETE', body: JSON.stringify(body) });
}
