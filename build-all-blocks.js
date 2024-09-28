const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const muPluginsDir = path.join(__dirname, 'web', 'app', 'mu-plugins');
const blockPrefix = 'eikonblock-';

fs.readdirSync(muPluginsDir).forEach(dir => {
  if (dir.startsWith(blockPrefix)) {
    const blockDir = path.join(muPluginsDir, dir);
    if (fs.existsSync(path.join(blockDir, 'package.json'))) {
      console.log(`Building block: ${dir}`);
      execSync('npm run build', { cwd: blockDir, stdio: 'inherit' });
    }
  }
});
