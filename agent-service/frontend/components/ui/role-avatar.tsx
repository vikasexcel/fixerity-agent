'use client';

import { User, Wrench } from 'lucide-react';
import { cn } from '@/lib/utils';

export function getInitials(name: string): string {
  return name
    .trim()
    .split(/\s+/)
    .map((s) => s[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
}

export function getRoleIcon(
  role: 'buyer' | 'seller',
  className?: string,
  size?: number
) {
  const s = size ?? 20;
  return role === 'buyer' ? (
    <User className={cn(className)} size={s} />
  ) : (
    <Wrench className={cn(className)} size={s} />
  );
}

interface RoleAvatarProps {
  name?: string;
  type: 'buyer' | 'seller';
  className?: string;
  size?: 'sm' | 'md' | 'lg';
}

const sizeClasses = {
  sm: 'h-8 w-8 text-xs',
  md: 'h-10 w-10 text-sm',
  lg: 'h-12 w-12 text-base',
};

export function RoleAvatar({ name, type, className, size = 'md' }: RoleAvatarProps) {
  const initials = name ? getInitials(name) : null;
  const icon = getRoleIcon(type, 'text-muted-foreground', size === 'sm' ? 16 : size === 'lg' ? 24 : 20);

  return (
    <div
      className={cn(
        'flex items-center justify-center rounded-full bg-muted text-muted-foreground flex-shrink-0',
        sizeClasses[size],
        className
      )}
    >
      {initials ? initials : icon}
    </div>
  );
}
