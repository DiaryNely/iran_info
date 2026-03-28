import { ValidationPipe } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';

async function bootstrap() {
  const app = await NestFactory.create(AppModule);
  const port = Number(process.env.PORT ?? 3000);

  app.setGlobalPrefix('api');
  app.useGlobalPipes(new ValidationPipe({ whitelist: true, transform: true }));
  app.enableCors({
    origin: (process.env.CORS_ORIGIN ?? '').split(',').map((origin) => origin.trim()),
    credentials: true,
  });

  await app.listen(port);
  console.log(`Backend running on http://localhost:${port}/api`);
}

bootstrap();
