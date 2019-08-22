Create the init file and then update `init_db.yml` with Peanut information.

```
cp init_db.yml.dist init_db.yml
```

Start server locally:

```
php -S localhost:8888 -t src src/App.php
```
