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
                              docker compose -f docker-compose.prod.yml config --quiet
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
                sh 'docker build --tag diasekovaltchuk-adv/app:$(git rev-parse --short=12 HEAD) .'
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
                    def profile = branch == 'main' ? 'prod' : 'dev'
                    def target = "/root/projects/${project}${suffix}"
                    def composeFiles = branch == 'main'
                        ? '-f docker-compose.prod.yml'
                        : '-f docker-compose.yml'

                    sh """
                        set -eu
                        cd '${target}'
                        git fetch origin
                        git reset --hard 'origin/${branch}'
                        git clean -fd
                        ln -sfn '/root/projects/envs/${project}${suffix}.env' .env
                        export COMPOSE_PROJECT_NAME='${project}${suffix}'
                        export IMAGE_TAG=\$(git rev-parse --short=12 HEAD)
                        docker compose ${composeFiles} build
                        docker compose ${composeFiles} down || true
                        docker compose ${composeFiles} up -d --wait --wait-timeout 120
                        docker compose ${composeFiles} ps
                    """
                }
            }
        }
    }
}
