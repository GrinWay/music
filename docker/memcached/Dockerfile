FROM memcached:1.6-alpine

# Настройки по умолчанию:
# -m 128  -> 128MB памяти
# -o modern -> новые оптимизации
# -v -> verbose (можно убрать в проде)
CMD ["memcached", "-m", "128", "-o", "modern", "-v"]
