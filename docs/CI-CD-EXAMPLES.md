# CI/CD Integration Examples

This document provides detailed examples for integrating the Laravel Source Code Obfuscator into various CI/CD platforms.

## Table of Contents

- [GitHub Actions](#github-actions)
- [GitLab CI](#gitlab-ci)
- [Jenkins](#jenkins)
- [Bitbucket Pipelines](#bitbucket-pipelines)
- [CircleCI](#circleci)
- [Travis CI](#travis-ci)
- [Azure Pipelines](#azure-pipelines)
- [AWS CodePipeline](#aws-codepipeline)

---

## GitHub Actions

### Basic Workflow

```yaml
name: Build and Obfuscate

on:
  push:
    branches: [main, production]
  pull_request:
    branches: [main]

jobs:
  obfuscate:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.2"
          extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite
          coverage: none

      - name: Install Composer dependencies
        run: composer install --no-dev --optimize-autoloader --prefer-dist

      - name: Install PHPBolt
        env:
          PHPBOLT_LICENSE: ${{ secrets.PHPBOLT_LICENSE }}
        run: |
          wget ${{ secrets.PHPBOLT_DOWNLOAD_URL }} -O phpbolt.tar.gz
          tar -xzf phpbolt.tar.gz
          sudo bash phpbolt-installer.sh
          phpbolt --activate $PHPBOLT_LICENSE

      - name: Verify Obfuscator Setup
        run: php artisan obfuscate:check

      - name: Run Obfuscation
        run: php artisan obfuscate:run --force --verbose

      - name: Upload Obfuscated Code
        uses: actions/upload-artifact@v3
        with:
          name: obfuscated-application-${{ github.sha }}
          path: build/obfuscated/
          retention-days: 30

      - name: Upload Obfuscation Report
        uses: actions/upload-artifact@v3
        with:
          name: obfuscation-report-${{ github.sha }}
          path: build/obfuscation-report.json
          retention-days: 30
```

### Advanced Workflow with Deployment

```yaml
name: Obfuscate and Deploy

on:
  push:
    branches: [production]

jobs:
  obfuscate:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.2"

      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-${{ hashFiles('**/composer.lock') }}

      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: Cache PHPBolt installation
        id: phpbolt-cache
        uses: actions/cache@v3
        with:
          path: |
            /usr/local/bin/phpbolt
            /usr/lib/php/extensions/phpbolt.so
          key: phpbolt-${{ runner.os }}-v1

      - name: Install PHPBolt
        if: steps.phpbolt-cache.outputs.cache-hit != 'true'
        env:
          PHPBOLT_LICENSE: ${{ secrets.PHPBOLT_LICENSE }}
        run: |
          wget ${{ secrets.PHPBOLT_DOWNLOAD_URL }} -O phpbolt.tar.gz
          tar -xzf phpbolt.tar.gz
          sudo bash phpbolt-installer.sh

      - name: Activate PHPBolt License
        env:
          PHPBOLT_LICENSE: ${{ secrets.PHPBOLT_LICENSE }}
        run: phpbolt --activate $PHPBOLT_LICENSE

      - name: Obfuscate Source Code
        run: |
          php artisan obfuscate:check
          php artisan obfuscate:run --force

      - name: Package Obfuscated Application
        run: |
          cd build/obfuscated
          tar -czf ../application.tar.gz .

      - name: Deploy to Production
        uses: appleboy/scp-action@master
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.PROD_USERNAME }}
          key: ${{ secrets.PROD_SSH_KEY }}
          source: "build/application.tar.gz"
          target: "/var/www/releases/${{ github.sha }}"

      - name: Activate Release
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.PROD_USERNAME }}
          key: ${{ secrets.PROD_SSH_KEY }}
          script: |
            cd /var/www/releases/${{ github.sha }}
            tar -xzf build/application.tar.gz
            rm build/application.tar.gz
            ln -snf /var/www/releases/${{ github.sha }} /var/www/current
            sudo systemctl reload php-fpm
```

---

## GitLab CI

### Basic Configuration

```yaml
stages:
  - build
  - obfuscate
  - deploy

variables:
  PHP_VERSION: "8.2"

cache:
  key: ${CI_COMMIT_REF_SLUG}
  paths:
    - vendor/

before_script:
  - apt-get update -qq
  - apt-get install -y -qq git curl

build:
  stage: build
  image: php:${PHP_VERSION}
  script:
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --no-dev --optimize-autoloader
  artifacts:
    paths:
      - vendor/
    expire_in: 1 hour

obfuscate:
  stage: obfuscate
  image: php:${PHP_VERSION}
  dependencies:
    - build
  before_script:
    - wget ${PHPBOLT_DOWNLOAD_URL} -O phpbolt.tar.gz
    - tar -xzf phpbolt.tar.gz
    - bash phpbolt-installer.sh
    - phpbolt --activate ${PHPBOLT_LICENSE}
  script:
    - php artisan obfuscate:check
    - php artisan obfuscate:run --force
  artifacts:
    paths:
      - build/obfuscated/
      - build/obfuscation-report.json
    expire_in: 1 week
  only:
    - main
    - production

deploy:production:
  stage: deploy
  dependencies:
    - obfuscate
  script:
    - "which ssh-agent || ( apt-get update -y && apt-get install openssh-client -y )"
    - eval $(ssh-agent -s)
    - ssh-add <(echo "$SSH_PRIVATE_KEY")
    - mkdir -p ~/.ssh
    - '[[ -f /.dockerenv ]] && echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config'
    - |
      scp -r build/obfuscated/* ${DEPLOY_USER}@${DEPLOY_HOST}:/var/www/html/
  only:
    - production
  when: manual
```

### With Docker

```yaml
stages:
  - build
  - obfuscate
  - package
  - deploy

build:
  stage: build
  image: composer:latest
  script:
    - composer install --no-dev --optimize-autoloader
  artifacts:
    paths:
      - vendor/

obfuscate:
  stage: obfuscate
  image: php:8.2-cli
  dependencies:
    - build
  before_script:
    - docker-php-ext-install pdo pdo_mysql
  script:
    - wget ${PHPBOLT_DOWNLOAD_URL} -O phpbolt.tar.gz
    - tar -xzf phpbolt.tar.gz
    - bash phpbolt-installer.sh
    - phpbolt --activate ${PHPBOLT_LICENSE}
    - php artisan obfuscate:run --force
  artifacts:
    paths:
      - build/obfuscated/

package:
  stage: package
  image: docker:latest
  services:
    - docker:dind
  dependencies:
    - obfuscate
  script:
    - docker build -t ${CI_REGISTRY_IMAGE}:${CI_COMMIT_SHA} .
    - docker push ${CI_REGISTRY_IMAGE}:${CI_COMMIT_SHA}
```

---

## Jenkins

### Declarative Pipeline

```groovy
pipeline {
    agent any

    environment {
        PHP_VERSION = '8.2'
        PHPBOLT_LICENSE = credentials('phpbolt-license')
        PHPBOLT_URL = credentials('phpbolt-download-url')
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-dev --optimize-autoloader'
            }
        }

        stage('Setup PHPBolt') {
            steps {
                sh '''
                    wget ${PHPBOLT_URL} -O phpbolt.tar.gz
                    tar -xzf phpbolt.tar.gz
                    bash phpbolt-installer.sh
                    phpbolt --activate ${PHPBOLT_LICENSE}
                '''
            }
        }

        stage('Verify Configuration') {
            steps {
                sh 'php artisan obfuscate:check'
            }
        }

        stage('Obfuscate') {
            steps {
                sh 'php artisan obfuscate:run --force --verbose'
            }
        }

        stage('Archive Artifacts') {
            steps {
                archiveArtifacts artifacts: 'build/obfuscated/**/*', fingerprint: true
                archiveArtifacts artifacts: 'build/obfuscation-report.json', fingerprint: true
            }
        }

        stage('Deploy') {
            when {
                branch 'production'
            }
            steps {
                sh '''
                    cd build/obfuscated
                    tar -czf ../release.tar.gz .
                    scp ../release.tar.gz ${DEPLOY_USER}@${DEPLOY_HOST}:/var/www/releases/${BUILD_NUMBER}/
                    ssh ${DEPLOY_USER}@${DEPLOY_HOST} "
                        cd /var/www/releases/${BUILD_NUMBER}
                        tar -xzf release.tar.gz
                        rm release.tar.gz
                        ln -snf /var/www/releases/${BUILD_NUMBER} /var/www/current
                    "
                '''
            }
        }
    }

    post {
        always {
            sh 'php artisan obfuscate:clear --force'
        }
        success {
            emailext (
                subject: "Build Success: ${env.JOB_NAME} - ${env.BUILD_NUMBER}",
                body: "Obfuscation completed successfully. Build artifacts are available.",
                to: "${env.CHANGE_AUTHOR_EMAIL}"
            )
        }
        failure {
            emailext (
                subject: "Build Failed: ${env.JOB_NAME} - ${env.BUILD_NUMBER}",
                body: "Obfuscation failed. Please check the build logs.",
                to: "${env.CHANGE_AUTHOR_EMAIL}"
            )
        }
    }
}
```

---

## Bitbucket Pipelines

```yaml
image: php:8.2

pipelines:
  default:
    - step:
        name: Build
        caches:
          - composer
        script:
          - apt-get update && apt-get install -y git zip unzip
          - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
          - composer install --no-dev --optimize-autoloader
        artifacts:
          - vendor/**

  branches:
    main:
      - step:
          name: Obfuscate
          script:
            - wget ${PHPBOLT_DOWNLOAD_URL} -O phpbolt.tar.gz
            - tar -xzf phpbolt.tar.gz
            - bash phpbolt-installer.sh
            - phpbolt --activate ${PHPBOLT_LICENSE}
            - php artisan obfuscate:check
            - php artisan obfuscate:run --force
          artifacts:
            - build/obfuscated/**
            - build/obfuscation-report.json

      - step:
          name: Deploy to Production
          deployment: production
          script:
            - pipe: atlassian/scp-deploy:1.2.0
              variables:
                USER: $DEPLOY_USER
                SERVER: $DEPLOY_HOST
                REMOTE_PATH: "/var/www/html"
                LOCAL_PATH: "build/obfuscated/*"
```

---

## CircleCI

```yaml
version: 2.1

executors:
  php:
    docker:
      - image: cimg/php:8.2

jobs:
  build:
    executor: php
    steps:
      - checkout
      - restore_cache:
          keys:
            - composer-v1-{{ checksum "composer.lock" }}
            - composer-v1-
      - run: composer install --no-dev --optimize-autoloader
      - save_cache:
          key: composer-v1-{{ checksum "composer.lock" }}
          paths:
            - vendor
      - persist_to_workspace:
          root: .
          paths:
            - vendor

  obfuscate:
    executor: php
    steps:
      - checkout
      - attach_workspace:
          at: .
      - run:
          name: Install PHPBolt
          command: |
            wget ${PHPBOLT_DOWNLOAD_URL} -O phpbolt.tar.gz
            tar -xzf phpbolt.tar.gz
            sudo bash phpbolt-installer.sh
            phpbolt --activate ${PHPBOLT_LICENSE}
      - run:
          name: Run Obfuscation
          command: |
            php artisan obfuscate:check
            php artisan obfuscate:run --force
      - store_artifacts:
          path: build/obfuscated
          destination: obfuscated-app
      - store_artifacts:
          path: build/obfuscation-report.json
      - persist_to_workspace:
          root: .
          paths:
            - build/obfuscated

  deploy:
    executor: php
    steps:
      - attach_workspace:
          at: .
      - run:
          name: Deploy to Production
          command: |
            cd build/obfuscated
            tar -czf ../release.tar.gz .
            scp ../release.tar.gz ${DEPLOY_USER}@${DEPLOY_HOST}:/var/www/releases/${CIRCLE_SHA1}/

workflows:
  version: 2
  build-obfuscate-deploy:
    jobs:
      - build
      - obfuscate:
          requires:
            - build
          filters:
            branches:
              only:
                - main
                - production
      - deploy:
          requires:
            - obfuscate
          filters:
            branches:
              only: production
```

---

## Best Practices for CI/CD

### 1. Cache PHPBolt Installation

```yaml
# Cache the PHPBolt binaries to speed up builds
cache:
  paths:
    - /usr/local/bin/phpbolt
    - /usr/lib/php/extensions/phpbolt.so
```

### 2. Secure Credentials

Always store sensitive data in CI/CD secrets:

- PHPBolt license keys
- Download URLs
- SSH keys
- Deployment credentials

### 3. Validate Before Obfuscating

```bash
php artisan obfuscate:check
```

### 4. Generate Reports

Enable report generation for audit trails:

```php
'ci_mode' => [
    'generate_report' => true,
    'report_path' => 'build/obfuscation-report.json',
],
```

### 5. Archive Artifacts

Always archive:

- Obfuscated code
- Obfuscation reports
- Backup files (optional)

### 6. Clean Up

Clean temporary files after deployment:

```bash
php artisan obfuscate:clear --force
```

---

**Need more examples?** Open an issue on GitHub with your CI/CD platform details.
