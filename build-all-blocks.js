const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const muPluginsDir = path.join(__dirname, 'web', 'app', 'mu-plugins');
const blockPrefix = 'eikonblocks-';


fs.readdirSync(muPluginsDir).forEach(dir => {
  if (dir.startsWith(blockPrefix)) {
    const blockDir = path.join(muPluginsDir, dir);
    if (fs.existsSync(path.join(blockDir, 'package.json'))) {
      console.log(`\n📦 Processing block: ${dir}`);

      // Check if node_modules exists, if not install dependencies
      if (!fs.existsSync(path.join(blockDir, 'node_modules'))) {
        console.log(`   → Installing dependencies...`);
        execSync('npm install', { cwd: blockDir, stdio: 'inherit' });
      }

      console.log(`   → Building...`);
      execSync('npm run build', { cwd: blockDir, stdio: 'inherit' });
      console.log(`   ✓ Done`);
    }
  }
});

console.log('\n✅ All blocks built successfully!');
