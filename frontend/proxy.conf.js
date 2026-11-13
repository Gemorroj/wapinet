// Dev-server proxy: API_TARGET defaults to http://localhost.
// Set it to the origin of your local Symfony app when it differs:
//   API_TARGET=http://your-local-host npm start
// PowerShell: $env:API_TARGET = "http://your-local-host"; npm start
const target = process.env.API_TARGET || 'http://localhost';

const paths = ['/api', '/resources', '/static', '/media'];

module.exports = Object.fromEntries(paths.map((path) => [path, { target, secure: false, changeOrigin: true }]));
