// js/auth.js
function localSaveToken(token) {
  localStorage.setItem('iugb_token', token);
}

function localSaveUser(obj) {
  // store minimal info: role, full_name, username
  localStorage.setItem('iugb_user', JSON.stringify(obj));
}

function getLocalUser() {
  const t = localStorage.getItem('iugb_user');
  if (!t) return null;
  return JSON.parse(t);
}

function requireAuth() {
  const token = localStorage.getItem('iugb_token');
  const user = getLocalUser();
  if (!token || !user) {
    window.location.href = 'index.html';
    throw new Error('Not authenticated');
  }
  return user;
}

function doLogout() {
  localStorage.removeItem('iugb_token');
  localStorage.removeItem('iugb_user');
  window.location.href = 'index.html';
}
