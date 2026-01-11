const { app, BrowserWindow, shell, ipcMain, safeStorage } = require('electron');
const path = require('node:path');
const fs = require('node:fs');
const crypto = require('node:crypto');

const isDev = process.env.NODE_ENV === 'development' || !app.isPackaged;
const devServerUrl = process.env.DEV_SERVER_URL || process.env.VITE_DEV_SERVER_URL || process.env.VITE_DEV_URL;
const defaultProdUrl = process.env.ELECTRON_DEFAULT_URL || 'https://simak-final-production.up.railway.app';
const shouldUseDefaultUrl = process.env.NODE_ENV === 'production' || app.isPackaged;
const electronAppUrl = process.env.ELECTRON_APP_URL || process.env.APP_URL || (shouldUseDefaultUrl ? defaultProdUrl : '');
const retryScheduleMs = [2000, 5000, 10000, 20000, 30000];
let retryAttempt = 0;
let retryTimer = null;
let mainWindowRef = null;

function logToFile(message) {
    try {
        const logPath = path.join(app.getPath('userData'), 'electron.log');
        const line = `[${new Date().toISOString()}] ${message}\n`;
        fs.appendFileSync(logPath, line, 'utf-8');
    } catch {
        // Best-effort logging only.
    }
}

function normalizeUrl(rawUrl) {
    if (!rawUrl) return '';
    return rawUrl.match(/^https?:\/\//) ? rawUrl : `https://${rawUrl}`;
}

function getRetryDelay() {
    const index = Math.min(retryAttempt, retryScheduleMs.length - 1);
    return retryScheduleMs[index];
}

function scheduleRetry(mainWindow, url, reason) {
    clearTimeout(retryTimer);
    const delay = getRetryDelay();
    retryAttempt += 1;
    logToFile(`Retry scheduled in ${delay}ms. reason=${reason || 'unknown'}`);
    retryTimer = setTimeout(() => {
        loadAppUrl(mainWindow, url);
    }, delay);
}

function showErrorPage(mainWindow, url, errorCode, errorDescription) {
    const message = errorCode === -138
        ? 'Network access is blocked. Please allow SIMAK in Windows Firewall/antivirus.'
        : 'Network request failed. Check your internet connection or backend URL.';
    const errorHtml = `
        <html>
            <head>
                <meta charset="utf-8">
                <title>Load error</title>
                <style>
                    body { font-family: Arial, sans-serif; background:#0b1224; color:#fff; padding:24px; }
                    h2 { margin:0 0 12px; }
                    .card { max-width: 720px; background:#0f1a33; padding:16px 20px; border-radius:10px; }
                    code { color:#a7f3d0; }
                    button { padding:8px 12px; border-radius:6px; border:0; cursor:pointer; margin-right:8px; }
                    .primary { background:#e11d48; color:#fff; }
                    .ghost { background:#1f2937; color:#fff; }
                    .muted { color:#cbd5f5; margin-top:12px; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>Failed to load app</h2>
                    <p><strong>URL:</strong> <code>${url}</code></p>
                    <p><strong>Error:</strong> <code>${errorDescription} (${errorCode})</code></p>
                    <p>${message}</p>
                    <div>
                        <button class="primary" onclick="window.desktop?.app?.reload?.()">Retry now</button>
                        <button class="ghost" onclick="window.desktop?.app?.openExternal?.('${url}')">Open in browser</button>
                    </div>
                    <p class="muted">Auto-retry is enabled. The app will reconnect when the network is available.</p>
                </div>
            </body>
        </html>`;
    mainWindow.loadURL(`data:text/html,${encodeURIComponent(errorHtml)}`);
}

function loadAppUrl(mainWindow, url) {
    if (!mainWindow || mainWindow.isDestroyed()) return;
    if (isDev && devServerUrl) {
        logToFile(`Loading dev server: ${devServerUrl}`);
        mainWindow.loadURL(devServerUrl);
        return;
    }

    const normalizedUrl = normalizeUrl(url);
    if (normalizedUrl) {
        logToFile(`Loading app URL: ${normalizedUrl}`);
        mainWindow.loadURL(normalizedUrl);
        return;
    }

    const indexPath = path.join(__dirname, 'public', 'build', 'index.html');
    logToFile(`Loading local file: ${indexPath}`);
    mainWindow.loadFile(indexPath);
}

// ==================== License Storage Helpers ====================

const LICENSE_FILES = {
    TOKEN: 'license_token.enc',
    DEVICE_ID: 'device_id.txt',
    LICENSE_DATA: 'license_data.json',
};

function getLicenseFilePath(filename) {
    return path.join(app.getPath('userData'), filename);
}

function generateDeviceId() {
    // Generate a persistent UUID for this device
    const deviceIdPath = getLicenseFilePath(LICENSE_FILES.DEVICE_ID);

    if (fs.existsSync(deviceIdPath)) {
        return fs.readFileSync(deviceIdPath, 'utf-8').trim();
    }

    // Generate new UUID
    const uuid = crypto.randomUUID();
    fs.writeFileSync(deviceIdPath, uuid, 'utf-8');
    return uuid;
}

// ==================== License IPC Handlers ====================

// Save token securely using safeStorage
ipcMain.handle('license:saveToken', async (_, token) => {
    try {
        if (!safeStorage.isEncryptionAvailable()) {
            // Fallback: save as plain text (less secure)
            fs.writeFileSync(getLicenseFilePath(LICENSE_FILES.TOKEN), token, 'utf-8');
            return true;
        }

        const encrypted = safeStorage.encryptString(token);
        fs.writeFileSync(getLicenseFilePath(LICENSE_FILES.TOKEN), encrypted);
        return true;
    } catch (error) {
        console.error('Failed to save token:', error);
        return false;
    }
});

// Get token from secure storage
ipcMain.handle('license:getToken', async () => {
    try {
        const tokenPath = getLicenseFilePath(LICENSE_FILES.TOKEN);

        if (!fs.existsSync(tokenPath)) {
            return null;
        }

        const data = fs.readFileSync(tokenPath);

        if (safeStorage.isEncryptionAvailable()) {
            return safeStorage.decryptString(data);
        } else {
            return data.toString('utf-8');
        }
    } catch (error) {
        console.error('Failed to get token:', error);
        return null;
    }
});

// Clear token
ipcMain.handle('license:clearToken', async () => {
    try {
        const tokenPath = getLicenseFilePath(LICENSE_FILES.TOKEN);
        if (fs.existsSync(tokenPath)) {
            fs.unlinkSync(tokenPath);
        }
        return true;
    } catch (error) {
        console.error('Failed to clear token:', error);
        return false;
    }
});

// Get or create device ID
ipcMain.handle('license:getDeviceId', async () => {
    return generateDeviceId();
});

// Save additional license data
ipcMain.handle('license:saveLicenseData', async (_, data) => {
    try {
        fs.writeFileSync(
            getLicenseFilePath(LICENSE_FILES.LICENSE_DATA),
            JSON.stringify(data, null, 2),
            'utf-8'
        );
        return true;
    } catch (error) {
        console.error('Failed to save license data:', error);
        return false;
    }
});

// Get license data
ipcMain.handle('license:getLicenseData', async () => {
    try {
        const dataPath = getLicenseFilePath(LICENSE_FILES.LICENSE_DATA);
        if (!fs.existsSync(dataPath)) {
            return null;
        }
        const content = fs.readFileSync(dataPath, 'utf-8');
        return JSON.parse(content);
    } catch (error) {
        console.error('Failed to get license data:', error);
        return null;
    }
});

// Clear all license data
ipcMain.handle('license:clearLicenseData', async () => {
    try {
        for (const file of Object.values(LICENSE_FILES)) {
            const filePath = getLicenseFilePath(file);
            if (fs.existsSync(filePath)) {
                fs.unlinkSync(filePath);
            }
        }
        return true;
    } catch (error) {
        console.error('Failed to clear license data:', error);
        return false;
    }
});

function createMainWindow() {
    const mainWindow = new BrowserWindow({
        width: 1366,
        height: 840,
        backgroundColor: '#0b1224',
        autoHideMenuBar: true,
        webPreferences: {
            preload: path.join(__dirname, 'preload.cjs'),
            contextIsolation: true,
            nodeIntegration: false,
            sandbox: false,
        },
    });

    loadAppUrl(mainWindow, electronAppUrl);

    mainWindow.webContents.on('did-finish-load', () => {
        const currentUrl = mainWindow.webContents.getURL();
        if (!currentUrl.startsWith('data:text/html')) {
            retryAttempt = 0;
            logToFile(`Page loaded successfully: ${currentUrl}`);
        }
    });

    mainWindow.webContents.on('did-fail-load', (_, errorCode, errorDescription, validatedURL) => {
        if (errorCode === -3) {
            return;
        }
        logToFile(`Load failed (${errorCode}): ${errorDescription} for ${validatedURL}`);
        showErrorPage(mainWindow, validatedURL || normalizeUrl(electronAppUrl), errorCode, errorDescription);
        scheduleRetry(mainWindow, electronAppUrl, `${errorCode}:${errorDescription}`);
    });

    mainWindow.webContents.on('console-message', (_, level, message) => {
        logToFile(`Console [${level}]: ${message}`);
    });

    mainWindow.webContents.on('render-process-gone', (_, details) => {
        logToFile(`Renderer gone: ${details.reason}`);
        scheduleRetry(mainWindow, electronAppUrl, `renderer:${details.reason}`);
    });

    if (process.env.ELECTRON_DEBUG === '1') {
        mainWindow.webContents.openDevTools({ mode: 'detach' });
    }

    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        shell.openExternal(url);
        return { action: 'deny' };
    });

    // Fix: Ensure proper focus handling for input fields
    // This fixes the issue where input fields require alt+tab to be clickable
    mainWindow.on('focus', () => {
        mainWindow.webContents.focus();
    });

    // Ensure focus is properly set when window is shown
    mainWindow.once('ready-to-show', () => {
        mainWindow.focus();
        mainWindow.webContents.focus();
    });

    // Handle blur/focus to ensure proper input field interaction
    mainWindow.on('blur', () => {
        // Window lost focus - this is normal
    });

    mainWindow.on('restore', () => {
        // When window is restored from minimized state, ensure focus
        mainWindow.webContents.focus();
    });

    return mainWindow;
}

app.whenReady().then(() => {
    app.setAppUserModelId('com.simak.desktop');
    logToFile(`App starting. isDev=${isDev} isPackaged=${app.isPackaged} userData=${app.getPath('userData')}`);
    mainWindowRef = createMainWindow();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createMainWindow();
        }
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

ipcMain.handle('runtime-info', () => ({
    isDev,
    isPackaged: app.isPackaged,
    appVersion: app.getVersion(),
    userDataPath: app.getPath('userData'),
    platform: process.platform,
}));

ipcMain.handle('app:reload', () => {
    retryAttempt = 0;
    if (mainWindowRef) {
        loadAppUrl(mainWindowRef, electronAppUrl);
    }
    return true;
});

ipcMain.handle('app:openExternal', (_, url) => {
    if (typeof url === 'string' && url.trim()) {
        shell.openExternal(url.trim());
    }
    return true;
});
