# Work From Office/Home Calendar


![WFO-1](./Screenshot WFO Calendar.png?raw=true)
![WFO-2](./Screenshot WFO Calendar 2.png?raw=true)

## env
```
cd api
cp .env.example .env
```

Update .env file

## Composer
```
cd api
composer install
```

# Migrate DB

## Windows:
```
.\vendor\bin\doctrine-migrations.bat migrate
```

## Linux:
```
./vendor/bin/doctrine-migrations migrate
```