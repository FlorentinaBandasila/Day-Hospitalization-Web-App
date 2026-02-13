// API Configuration
// Update this baseURL to match your backend server location
const API_CONFIG = {
    baseURL: 'http://localhost/eessp/backend', // Change this to your server URL
    endpoints: {
        pacienti: '/api_pacienti.php',
        doctori: '/api_doctori.php',
        spitalizari: '/api_spitalizari.php'
    }
};

// API Helper Functions
const API = {
    async get(endpoint, params = {}) {
        const url = new URL(API_CONFIG.baseURL + endpoint);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

        const response = await fetch(url);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Request failed');
        }
        return await response.json();
    },

    async post(endpoint, data) {
        const response = await fetch(API_CONFIG.baseURL + endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Request failed');
        }
        return await response.json();
    },

    async put(endpoint, data) {
        const response = await fetch(API_CONFIG.baseURL + endpoint, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Request failed');
        }
        return await response.json();
    },

    async delete(endpoint, params = {}) {
        const url = new URL(API_CONFIG.baseURL + endpoint);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

        const response = await fetch(url, {
            method: 'DELETE'
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Request failed');
        }
        return await response.json();
    }
};
