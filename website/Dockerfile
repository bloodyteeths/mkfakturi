FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
# Google Analytics 4 measurement ID. NEXT_PUBLIC_* vars are inlined at build
# time, so it must be present here. This ID is public (visible in page source),
# not a secret. Override at build with --build-arg if needed.
ENV NEXT_PUBLIC_GA_ID=G-PCQN5YNTH8
RUN npm run build

FROM node:20-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static
COPY --from=builder /app/public ./public
EXPOSE 3000
CMD ["node", "server.js"]
