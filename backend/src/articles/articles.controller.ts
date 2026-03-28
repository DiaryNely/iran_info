import { Controller, Get, Param } from '@nestjs/common';

const sampleArticles = [
  {
    id: 1,
    slug: 'situation-geopolitique-iran',
    title: 'Situation geopolitique en Iran',
    excerpt: 'Contexte general et principaux enjeux regionaux.',
    content: 'Contenu detaille de demonstration.',
  },
  {
    id: 2,
    slug: 'chronologie-evenements-recents',
    title: 'Chronologie des evenements recents',
    excerpt: 'Resume des faits marquants.',
    content: 'Contenu detaille de demonstration.',
  },
];

@Controller('articles')
export class ArticlesController {
  @Get()
  findAll() {
    return sampleArticles;
  }

  @Get(':slug')
  findOneBySlug(@Param('slug') slug: string) {
    return sampleArticles.find((article) => article.slug === slug) ?? null;
  }
}
