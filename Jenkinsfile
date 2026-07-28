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
                sh 'docker build --tag diasekovaltchuk-adv:verify .'
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
                            suffix=""
                            [ "$PIPELINE_BRANCH" = "dev" ] && suffix="-dev"
                            env_file="/root/projects/envs/diasekovaltchuk-adv${suffix}.env"
                            test -f "$env_file"
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
                    def composeFiles = branch == 'main'
                        ? '-f docker-compose-prod.yml'
                        : '-f docker-compose.yml'

                    sh """
                        set -eu
                        env_file='/root/projects/envs/${project}${suffix}.env'
                        test -f "\$env_file"
                        ln -sfn "\$env_file" .env
                        export COMPOSE_PROJECT_NAME='${project}${suffix}'
                        export IMAGE_TAG=\$(git rev-parse --short=12 HEAD)
                        docker image inspect "${project}/app:\$IMAGE_TAG${suffix}" >/dev/null
                        docker compose ${composeFiles} down --remove-orphans || true
                        docker compose ${composeFiles} up -d --no-build --pull never --remove-orphans --wait --wait-timeout 120
                        docker compose ${composeFiles} ps
                    """
                }
            }
        }
    }
}
