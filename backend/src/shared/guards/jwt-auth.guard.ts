import { CanActivate, ExecutionContext, Injectable, UnauthorizedException } from '@nestjs/common';
import { JwtService } from '@nestjs/jwt';
import { Request } from 'express';
import { AuthService } from '../../auth/services/auth.service';
import { JwtUser } from '../types/jwt-user.type';

type RequestWithUser = Request & {
  headers: {
    authorization?: string;
  };
  user?: JwtUser;
};

@Injectable()
export class JwtAuthGuard implements CanActivate {
  constructor(
    private readonly jwtService: JwtService,
    private readonly authService: AuthService,
  ) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const request = context.switchToHttp().getRequest<RequestWithUser>();
    const authHeader = request.headers.authorization;

    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      throw new UnauthorizedException('Missing bearer token');
    }

    const token = authHeader.substring('Bearer '.length).trim();
    const isRevoked = this.authService.isTokenRevoked(token);
    if (isRevoked) {
      throw new UnauthorizedException('Token has been revoked');
    }

    try {
      const payload = await this.jwtService.verifyAsync<JwtUser>(token, {
        secret: process.env.JWT_SECRET ?? 'change-me-in-production',
      });
      request.user = payload;
      return true;
    } catch {
      throw new UnauthorizedException('Invalid or expired token');
    }
  }
}
