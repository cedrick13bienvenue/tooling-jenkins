pipeline {
  agent any

      environment
    {
        PROJECT     = 'zooto-tooling-prod'
        ECRURL      = '059636857273.dkr.ecr.eu-central-1.amazonaws.com'
    }

  stages {

    stage("Initial cleanup") {
        steps {
        dir("${WORKSPACE}") {
            deleteDir()
        }
        }
    }

    stage('Checkout')
    {
      steps {
      checkout([
        $class: 'GitSCM',
        doGenerateSubmoduleConfigurations: false,
        extensions: [],
        submoduleCfg: [],
        userRemoteConfigs: [[url: "https://github.com/cedrick13bienvenue/tooling-jenkins.git",credentialsId:'GITHUB_CREDENTIALS']]
        ])

      }
        }

          stage('Build preparations')
        {
            steps
            {
                script
                {
                    gitCommitHash = sh(returnStdout: true, script: 'git rev-parse HEAD').trim()
                    shortCommitHash = gitCommitHash.take(7)
                    VERSION = shortCommitHash
                    currentBuild.displayName = "#${BUILD_ID}-${VERSION}"
                    IMAGE = "$PROJECT:$VERSION"
                }
            }
        }

    stage('Build For Dev Environment') {
               when {
                expression { BRANCH_NAME ==~ /(dev)/ }
            }
        steps {
            echo 'Build Dockerfile....'
            script {
                sh("aws ecr get-login-password --region eu-central-1 | docker login --username AWS --password-stdin https://$ECRURL")
                sh "docker build --network=host -t $IMAGE ."
                docker.withRegistry("https://$ECRURL"){
                docker.image("$IMAGE").push("dev-$BUILD_NUMBER")
            }
            }
        }
      }

    stage('Build For Staging Environment') {
               when {
                expression { BRANCH_NAME ==~ /(staging|master)/ }
            }
        steps {
            echo 'Build Dockerfile....'
            script {
                sh("aws ecr get-login-password --region eu-central-1 | docker login --username AWS --password-stdin https://$ECRURL")
                sh "docker build --network=host -t $IMAGE ."
                docker.withRegistry("https://$ECRURL"){
                docker.image("$IMAGE").push("staging-$BUILD_NUMBER")
            }
            }
        }
    }

    stage('Build For Production Environment') {
        when { tag "release-*" }
        steps {
            echo 'Build Dockerfile....'
            script {
                sh("aws ecr get-login-password --region eu-central-1 | docker login --username AWS --password-stdin https://$ECRURL")
                sh "docker build --network=host -t $IMAGE ."
                docker.withRegistry("https://$ECRURL"){
                docker.image("$IMAGE").push("prod-$BUILD_NUMBER")
            }
            }
        }
    }

    }

        post
    {
        always
        {
            sh "docker rmi -f $IMAGE "
        }
    }
}
