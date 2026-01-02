const { app, BrowserWindow, shell, ipcMain, safeStorage } = require('electron');
const path = require('node:path');
const fs = require('node:fs');
const crypto = require('node:crypto');

const isDev = process.env.NODE_ENV === 'development' || !app.isPackaged;
const devServerUrl = process.env.DEV_SERVER_URL || process.env.VITE_DEV_SERVER_URL || process.env.VITE_DEV_URL;

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

    if (isDev && devServerUrl) {
        mainWindow.loadURL(devServerUrl);
        mainWindow.webContents.openDevTools({ mode: 'detach' });
    } else {
        const indexPath = path.join(__dirname, 'public', 'build', 'index.html');
        mainWindow.loadFile(indexPath);
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
    createMainWindow();

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
