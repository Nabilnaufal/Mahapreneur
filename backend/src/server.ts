import { createApp } from './app.js';
import { env } from './config/env.js';
import { prisma } from './config/prisma.js';

const app = createApp();

const server = app.listen(env.port, () => {
  console.log(`Mahapreneur API siap di ${env.apiUrl} (${env.nodeEnv})`);
});

for (const signal of ['SIGINT', 'SIGTERM'] as const) {
  process.on(signal, async () => {
    console.log(`\nMenutup server (${signal})...`);
    server.close();
    await prisma.$disconnect();
    process.exit(0);
  });
}
