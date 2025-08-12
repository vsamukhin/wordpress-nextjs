import { withFaust } from '@faustwp/core';
import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  reactStrictMode: true,
};

export default withFaust(nextConfig);
