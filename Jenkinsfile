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
    // DOCKERHUB_CREDENTIALS = credentials('dockerhub')
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
            composer update
        """
      }
    }

    stage("BUILD") {
      steps {
        script {
            FAILED_STAGE=env.STAGE_NAME
            echo "BUILD"

            dockerImage = docker.build("apergudev/privy-aczd-module:${GIT_HASH}")
        }
      }
    }

    stage("RELEASE") {
      steps {
        script {
          FAILED_STAGE=env.STAGE_NAME
          echo "RELEASE"

          withDockerRegistry([ credentialsId: "dockerhub", url: "" ]){
                // dockerImage.push()
                dockerImage.push("latest")
                dockerImage.push("dev")
                dockerImage.push("staging")
                // dockerImage.push(env.BRANCH_NAME)
          }
        }
      }
    }
  }
}
