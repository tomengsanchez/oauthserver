<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ecosys User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Simple transition for modals */
        .modal {
            transition: opacity 0.25s ease;
        }
        body.modal-active {
            overflow-x: hidden;
            overflow-y: hidden;
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Main Container -->
    <div id="app" class="container mx-auto p-4 sm:p-6 lg:p-8">

        <!-- Login View -->
        <div id="login-view">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden mt-10">
                <div class="p-8">
                    <h2 class="text-2xl font-semibold text-gray-700 text-center">Ecosys Admin Login</h2>
                    <p class="text-sm text-gray-600 text-center mt-2">Sign in to manage users</p>
                    <form id="login-form" class="mt-6">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="client-id">Client ID</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="client-id" type="text" placeholder="Client ID" value="testclient">
                        </div>
                         <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="client-secret">Client Secret</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="client-secret" type="password" placeholder="Client Secret" value="testsecret">
                        </div>
                        <hr class="my-6">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" type="text" placeholder="Username" value="testuser">
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" placeholder="******************" value="testpass">
                        </div>
                        <div id="login-error" class="text-red-500 text-xs italic mb-4 hidden"></div>
                        <div class="flex items-center justify-between">
                            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                                Sign In
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Dashboard View -->
        <div id="dashboard-view" class="hidden">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
                <div>
                    <span id="welcome-message" class="text-gray-600 mr-4"></span>
                    <button id="logout-button" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Logout</button>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">All Users</h2>
                    <button id="add-user-button" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        + Add New User
                    </button>
                </div>
                <!-- User Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                            <tr>
                                <th class="py-3 px-6 text-left">Username</th>
                                <th class="py-3 px-6 text-left">Full Name</th>
                                <th class="py-3 px-6 text-left">Email</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="user-table-body" class="text-gray-600 text-sm font-light">
                            <!-- User rows will be inserted here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Form Modal (for Add/Edit) -->
    <div id="user-modal" class="modal fixed w-full h-full top-0 left-0 flex items-center justify-center hidden">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50 overflow-y-auto">
            <div class="modal-content py-4 text-left px-6">
                <div class="flex justify-between items-center pb-3">
                    <p id="modal-title" class="text-2xl font-bold">Add User</p>
                    <button id="modal-close" class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </button>
                </div>
                <form id="user-form">
                    <input type="hidden" id="edit-username">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="form-username">Username</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="form-username" type="text" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="form-password">Password</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="form-password" type="password">
                        <p id="password-help" class="text-xs text-gray-600 mt-1">Required for new users. Leave blank to keep existing password when editing.</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="form-first-name">First Name</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="form-first-name" type="text">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="form-last-name">Last Name</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="form-last-name" type="text">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="form-email">Email</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="form-email" type="email" required>
                    </div>
                    <div id="form-error" class="text-red-500 text-xs italic mb-4 hidden"></div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- CONFIGURATION ---
            const API_BASE_URL = 'https://ithelp.ecosyscorp.ph/etc-backend';
            const REQUIRED_SCOPES = 'users:read users:create users:update users:delete';

            // --- STATE ---
            let accessToken = null;

            // --- UI ELEMENTS ---
            const loginView = document.getElementById('login-view');
            const dashboardView = document.getElementById('dashboard-view');
            const loginForm = document.getElementById('login-form');
            const loginError = document.getElementById('login-error');
            const userTableBody = document.getElementById('user-table-body');
            const addUserButton = document.getElementById('add-user-button');
            const logoutButton = document.getElementById('logout-button');
            const welcomeMessage = document.getElementById('welcome-message');

            // Modal elements
            const userModal = document.getElementById('user-modal');
            const modalTitle = document.getElementById('modal-title');
            const userForm = document.getElementById('user-form');
            const formError = document.getElementById('form-error');
            const passwordHelp = document.getElementById('password-help');
            const modalCloseButton = document.getElementById('modal-close');

            // --- API HELPERS ---
            
            /**
             * Handles API login.
             * @param {string} clientId
             * @param {string} clientSecret
             * @param {string} username
             * @param {string} password
             */
            const apiLogin = async (clientId, clientSecret, username, password) => {
                const params = new URLSearchParams();
                params.append('grant_type', 'password');
                params.append('client_id', clientId);
                params.append('client_secret', clientSecret);
                params.append('username', username);
                params.append('password', password);
                params.append('scope', REQUIRED_SCOPES);
                
                try {
                    const response = await fetch(`${API_BASE_URL}/token`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: params
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Login failed.');
                    }
                    accessToken = data.access_token;
                    return true;
                } catch (error) {
                    showLoginError(error.message);
                    return false;
                }
            };

            /**
             * Generic authenticated fetch request.
             * @param {string} endpoint
             * @param {object} options
             */
            const authFetch = async (endpoint, options = {}) => {
                const defaultOptions = {
                    headers: {
                        'Authorization': `Bearer ${accessToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                };
                
                const response = await fetch(`${API_BASE_URL}${endpoint}`, { ...defaultOptions, ...options });
                if (response.status === 204) return true; // For successful DELETE requests
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `An error occurred: ${response.statusText}`);
                }
                return response.json();
            };

            // --- UI LOGIC ---

            /**
             * Renders the user table.
             */
            const renderUsers = async () => {
                try {
                    const data = await authFetch('/api/users');
                    userTableBody.innerHTML = ''; // Clear existing rows
                    data.users.forEach(user => {
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-200 hover:bg-gray-100';
                        row.innerHTML = `
                            <td class="py-3 px-6 text-left whitespace-nowrap">${user.username}</td>
                            <td class="py-3 px-6 text-left">${user.first_name || ''} ${user.last_name || ''}</td>
                            <td class="py-3 px-6 text-left">${user.email}</td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center">
                                    <button data-username="${user.username}" class="edit-btn w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z" /></svg>
                                    </button>
                                    <button data-username="${user.username}" class="delete-btn w-4 mr-2 transform hover:text-red-500 hover:scale-110">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        `;
                        userTableBody.appendChild(row);
                    });
                } catch (error) {
                    alert(`Error fetching users: ${error.message}`);
                    logout();
                }
            };
            
            const showLoginError = (message) => {
                loginError.textContent = message;
                loginError.classList.remove('hidden');
            };

            const hideLoginError = () => {
                loginError.classList.add('hidden');
            };
            
            const showFormError = (message) => {
                formError.textContent = message;
                formError.classList.remove('hidden');
            };

            const hideFormError = () => {
                formError.classList.add('hidden');
            };

            const showDashboard = () => {
                loginView.classList.add('hidden');
                dashboardView.classList.remove('hidden');
            };

            const showLogin = () => {
                dashboardView.classList.add('hidden');
                loginView.classList.remove('hidden');
            };
            
            const openModal = () => {
                userModal.classList.remove('hidden');
                document.body.classList.add('modal-active');
            };
            
            const closeModal = () => {
                userModal.classList.add('hidden');
                userForm.reset();
                document.getElementById('edit-username').value = '';
                document.getElementById('form-username').disabled = false;
                passwordHelp.classList.remove('hidden');
                hideFormError();
                document.body.classList.remove('modal-active');
            };
            
            const logout = () => {
                accessToken = null;
                showLogin();
            };

            // --- EVENT LISTENERS ---

            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                hideLoginError();
                const clientId = document.getElementById('client-id').value;
                const clientSecret = document.getElementById('client-secret').value;
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                const success = await apiLogin(clientId, clientSecret, username, password);
                if (success) {
                    welcomeMessage.textContent = `Welcome, ${username}!`;
                    showDashboard();
                    renderUsers();
                }
            });

            logoutButton.addEventListener('click', logout);
            
            addUserButton.addEventListener('click', () => {
                modalTitle.textContent = 'Add New User';
                userForm.reset();
                openModal();
            });

            modalCloseButton.addEventListener('click', closeModal);
            userModal.querySelector('.modal-overlay').addEventListener('click', closeModal);

            userForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                hideFormError();

                const editUsername = document.getElementById('edit-username').value;
                const isEditing = !!editUsername;
                
                const userData = {
                    username: document.getElementById('form-username').value,
                    password: document.getElementById('form-password').value,
                    first_name: document.getElementById('form-first-name').value,
                    last_name: document.getElementById('form-last-name').value,
                    email: document.getElementById('form-email').value,
                };
                
                let endpoint = '/api/users';
                let method = 'POST';
                
                if (isEditing) {
                    endpoint = `/api/users/${editUsername}`;
                    method = 'PATCH';
                    // Don't send password if it's blank during an edit
                    if (!userData.password) {
                        delete userData.password;
                    }
                    // Username cannot be changed
                    delete userData.username;
                } else {
                    // Password is required for new users
                    if (!userData.password) {
                        showFormError('Password is required for new users.');
                        return;
                    }
                }

                try {
                    await authFetch(endpoint, {
                        method: method,
                        body: JSON.stringify(userData)
                    });
                    closeModal();
                    renderUsers();
                } catch (error) {
                    showFormError(error.message);
                }
            });

            userTableBody.addEventListener('click', async (e) => {
                const editButton = e.target.closest('.edit-btn');
                const deleteButton = e.target.closest('.delete-btn');

                if (editButton) {
                    const username = editButton.dataset.username;
                    try {
                        const data = await authFetch(`/api/users/${username}`);
                        const user = data.user;
                        
                        modalTitle.textContent = 'Edit User';
                        document.getElementById('edit-username').value = user.username;
                        document.getElementById('form-username').value = user.username;
                        document.getElementById('form-username').disabled = true; // Don't allow username edits
                        document.getElementById('form-password').value = '';
                        passwordHelp.classList.remove('hidden');
                        document.getElementById('form-first-name').value = user.first_name || '';
                        document.getElementById('form-last-name').value = user.last_name || '';
                        document.getElementById('form-email').value = user.email || '';
                        
                        openModal();

                    } catch (error) {
                        alert(`Error fetching user details: ${error.message}`);
                    }
                }

                if (deleteButton) {
                    const username = deleteButton.dataset.username;
                    if (confirm(`Are you sure you want to delete the user "${username}"? This action cannot be undone.`)) {
                        try {
                            await authFetch(`/api/users/${username}`, { method: 'DELETE' });
                            renderUsers();
                        } catch (error) {
                            alert(`Error deleting user: ${error.message}`);
                        }
                    }
                }
            });

        });
    </script>
</body>
</html>
