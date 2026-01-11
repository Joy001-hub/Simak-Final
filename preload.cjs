const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('desktop', {
    runtimeInfo: () => ipcRenderer.invoke('runtime-info'),
    isElectron: true,
    app: {
        reload: () => ipcRenderer.invoke('app:reload'),
        openExternal: (url) => ipcRenderer.invoke('app:openExternal', url),
    },

    // License management API
    license: {
        saveToken: (token) => ipcRenderer.invoke('license:saveToken', token),
        getToken: () => ipcRenderer.invoke('license:getToken'),
        clearToken: () => ipcRenderer.invoke('license:clearToken'),
        getDeviceId: () => ipcRenderer.invoke('license:getDeviceId'),
        saveLicenseData: (data) => ipcRenderer.invoke('license:saveLicenseData', data),
        getLicenseData: () => ipcRenderer.invoke('license:getLicenseData'),
        clearLicenseData: () => ipcRenderer.invoke('license:clearLicenseData'),
    },
});
