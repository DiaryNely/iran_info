import { BadRequestException } from '@nestjs/common';
import { diskStorage } from 'multer';
import { extname } from 'path';

function sanitizeName(name: string) {
  return name
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-zA-Z0-9-_]/g, '-')
    .replace(/-+/g, '-')
    .toLowerCase();
}

export const articleUploadOptions = {
  storage: diskStorage({
    destination: './uploads/articles',
    filename: (_req: unknown, file: Express.Multer.File, callback: (error: Error | null, filename: string) => void) => {
      const extension = extname(file.originalname || '').toLowerCase();
      const baseName = sanitizeName(file.originalname.replace(extension, '') || 'image');
      const uniqueName = `${Date.now()}-${Math.round(Math.random() * 1e9)}-${baseName}${extension}`;
      callback(null, uniqueName);
    },
  }),
  fileFilter: (_req: unknown, file: Express.Multer.File, callback: (error: Error | null, acceptFile: boolean) => void) => {
    if (!file.mimetype.startsWith('image/')) {
      callback(new BadRequestException('Only image files are allowed') as unknown as Error, false);
      return;
    }

    callback(null, true);
  },
  limits: {
    fileSize: 5 * 1024 * 1024,
  },
};
