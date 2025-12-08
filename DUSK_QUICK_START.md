# Dusk Testing Quick Start

## One-Line Test Execution

```bash
./run-dusk-tests.sh
```

## Common Commands

| Command | Description |
|---------|-------------|
| `./run-dusk-tests.sh` | Run all Dusk tests |
| `./run-dusk-tests.sh --filter=TestName` | Run specific test |
| `./run-dusk-tests.sh --stop-on-failure` | Stop on first failure |
| `make -f Makefile.dusk dusk-test` | Run all tests (using Make) |
| `make -f Makefile.dusk dusk-vnc` | Open VNC viewer |

## Manual Docker Commands

```bash
# Start containers (v2)
docker compose -f docker-compose.dusk.yml up -d

# Run tests
docker compose -f docker-compose.dusk.yml exec app php artisan dusk

# Stop containers
docker compose -f docker-compose.dusk.yml down

# Note: If using Docker Compose v1, use 'docker-compose' instead of 'docker compose'
```

## Watch Tests Live

Open in browser: **http://localhost:7900** (password: `secret`)

## Troubleshooting

```bash
# View logs
docker-compose -f docker-compose.dusk.yml logs app
docker-compose -f docker-compose.dusk.yml logs selenium

# Restart everything
docker-compose -f docker-compose.dusk.yml restart

# Clean slate
docker-compose -f docker-compose.dusk.yml down -v
```

## Need Help?

See full documentation: [DUSK_TESTING.md](DUSK_TESTING.md)
