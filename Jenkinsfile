pipeline {
    agent any

    options {
        disableConcurrentBuilds()
        timeout(time: 45, unit: 'MINUTES')
    }

    stages {
        stage('Install') {
            steps {
                echo 'Dependencias sao instaladas no build multi-stage do Dockerfile.'
            }
        }

        stage('Verify') {
            steps {
                sh '''
                    set -eu
                    docker build --target backend-quality --tag diasekovaltchuk-adv:backend-verify .
                    docker build --target frontend-quality --tag diasekovaltchuk-adv:frontend-verify .
                '''
            }
        }

        stage('Compose') {
            steps {
                script {
                    def branch = env.BRANCH_NAME ?: env.GIT_BRANCH ?: ''
                    branch = branch.replaceFirst(/^origin\//, '')
                    if (branch != 'main' && branch != 'dev') {
                        echo "Branch sem Compose de entrega: ${branch}"
                        return
                    }
                    withEnv(["PIPELINE_BRANCH=${branch}"]) {
                        sh '''
                            set -eu
                            suffix="-prod"
                            stage_dir="prod"
                            if [ "$PIPELINE_BRANCH" = "dev" ]; then
                              suffix="-dev"
                              stage_dir="dev"
                            fi
                            config_dir="/srv/vps/projects/envs/diasekovaltchuk-adv/${stage_dir}"
                            env_file="${config_dir}/diasekovaltchuk-adv${suffix}.env"
                            test -f "$env_file"
                            test -d "${config_dir}/secrets"
                            for secret in app_key mail_password turnstile_secret_key recaptcha_secret_key hcaptcha_secret_key; do
                              test -f "${config_dir}/secrets/${secret}"
                            done
                            ln -sfn "$env_file" .env
                            if [ "$PIPELINE_BRANCH" = "main" ]; then
                              export COMPOSE_PROJECT_NAME=diasekovaltchuk-adv
                              docker compose -f docker-compose-prod.yml config --quiet
                            else
                              export COMPOSE_PROJECT_NAME=diasekovaltchuk-adv-dev
                              docker compose -f docker-compose.yml config --quiet
                            fi
                        '''
                    }
                }
            }
        }

        stage('Container') {
            steps {
                script {
                    def branch = env.BRANCH_NAME ?: env.GIT_BRANCH ?: ''
                    branch = branch.replaceFirst(/^origin\//, '')
                    def suffix = branch == 'dev' ? '-dev' : ''
                    sh "docker build --tag diasekovaltchuk-adv/app:\$(git rev-parse --short=12 HEAD)${suffix} ."
                }
            }
        }

        stage('Deploy') {
            steps {
                script {
                    def branch = env.BRANCH_NAME ?: env.GIT_BRANCH ?: ''
                    branch = branch.replaceFirst(/^origin\//, '')
                    def project = 'diasekovaltchuk-adv'

                    if (branch != 'main' && branch != 'dev') {
                        echo "Branch sem deploy: ${branch}"
                        return
                    }

                    def suffix = branch == 'main' ? '' : '-dev'
                    def stageDir = branch == 'main' ? 'prod' : 'dev'
                    def envSuffix = branch == 'main' ? '-prod' : '-dev'
                    def composeFiles = branch == 'main'
                        ? '-f docker-compose-prod.yml'
                        : '-f docker-compose.yml'

                    sh """
                        set -eu
                        config_dir='/srv/vps/projects/envs/${project}/${stageDir}'
                        env_file="\$config_dir/${project}${envSuffix}.env"
                        test -f "\$env_file"
                        test -d "\$config_dir/secrets"
                        for secret in app_key mail_password turnstile_secret_key recaptcha_secret_key hcaptcha_secret_key; do
                          test -f "\$config_dir/secrets/\$secret"
                        done
                        ln -sfn "\$env_file" .env
                        export COMPOSE_PROJECT_NAME='${project}${suffix}'
                        export IMAGE_TAG=\$(git rev-parse --short=12 HEAD)
                        docker image inspect "${project}/app:\$IMAGE_TAG${suffix}" >/dev/null
                        target='/srv/vps/projects/${project}/${stageDir}'
                        mkdir -p "\$target"
                        tar --exclude='./.git' --exclude='./.env' \
                          --exclude='./node_modules' --exclude='./backend/vendor' \
                          --exclude='./backend/coverage.xml' --exclude='./frontend/coverage' \
                          --exclude='./secrets' \
                          -cf - . | tar -C "\$target" -xf -
                        cd "\$target"
                        ln -sfn "\$env_file" .env
                        docker compose ${composeFiles} config --quiet
                        docker compose ${composeFiles} down --remove-orphans || true
                        docker compose ${composeFiles} up -d --no-build --pull never --remove-orphans --wait --wait-timeout 120
                        docker compose ${composeFiles} ps
                    """
                }
            }
        }
    }
}
