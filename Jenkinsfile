properties(
[pipelineTriggers([pollSCM('* * * * *')])]
)

def FAILED_STAGE

pipeline {
  agent any

  //environment
  environment {
    // Repository
    // def GIT_CREDENTIAL = "git.dev1.my.id"
    def GIT_HASH = sh(returnStdout: true, script: 'git log -1 --pretty=format:"%h"').trim()
    DOCKERHUB_CREDENTIALS = credentials('dockerhub-apergu')
  }

  stages {
    stage("PREPARE") {
      steps {
        script {
            FAILED_STAGE=env.STAGE_NAME
            echo "PREPARE"
        }

        // Install Script
        sh label: 'Preparation Script', script:
        """
            composer update --ignore-platform-reqs
        """
      }
    }

    stage("BUILD") {
      steps {
        script {
            FAILED_STAGE=env.STAGE_NAME
            echo "BUILD"

             sh label: 'Build Script', script:
            """
                docker build -t apergudev/privy-aczd-module:latest .
            """
        }
      }
    }

    stage("RELEASE") {
      steps {
        script {
          FAILED_STAGE=env.STAGE_NAME
          echo "RELEASE"
        }

        sh label: 'STEP RELEASE', script:
        """
          echo $DOCKERHUB_CREDENTIALS_PSW | docker login -u $DOCKERHUB_CREDENTIALS_USR --password-stdin
          docker push apergudev/privy-aczd-module:latest
          docker push apergudev/privy-aczd-module:staging
          docker push apergudev/privy-aczd-module:dev
        """
      }
    }
  }
}
