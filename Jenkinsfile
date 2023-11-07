properties(
[pipelineTriggers([pollSCM('* * * * *')])]
)

def FAILED_STAGE

pipeline {
  agent any

  //environment
  environment {
    // Repository
    def GIT_CREDENTIAL = "git.dev1.my.id"
    // def GIT_URL = sh(returnStdout: true, script: 'git config --get remote.origin.url').trim()
    // def GIT_NAME = sh(returnStdout: true, script: 'basename -s .git `git config --get remote.origin.url`').trim()
    def GIT_HASH = sh(returnStdout: true, script: 'git log -1 --pretty=format:"%h"').trim()
    // def GIT_SUBJECT = sh(returnStdout: true, script: 'git log -1 --pretty=format:"%s"').trim()

    // def GIT_TAG = sh(returnStdout: true, script: 'git describe --tags `git rev-list --tags --max-count=1`').trim()




    // def AUTHOR_NAME = sh(returnStdout: true, script: 'git log -1 --pretty=format:"%an"').trim()
    // def AUTHOR_EMAIL = sh(returnStdout: true, script: 'git log -1 --pretty=format:"%ae"').trim()
    // def AUTHOR_DATE_RELATIVE = sh(returnStdout: true, script: 'git log -1 --pretty=format:"%ar"').trim()

    // // Slack Notification
    // def NOTIFICATION="false"
    // def ALWAYS ="1974D2"
    // def FAILURE="ee000d"
    // def SUCCESS="1fff00"
    // def HOOKS="https://hooks.slack.com/services/***"
  }

  stages {
    stage("PREPARE") {
      steps {
        script {
            // FAILED_STAGE=env.STAGE_NAME
            echo "PREPARE"
        }

        // Install Script
        sh label: 'Preparation Script', script:
            // cp .env.dev .env
        """
            composer i
        """

        // // PULL REPO
        // git branch: """${env.BRANCH_NAME}""",
        // credentialsId: """${GIT_CREDENTIAL}""",
        // url: """${GIT_URL}"""

        // // Install Script
        // sh label: 'Script Installation', script:
        // """
        //   make install namespace=${env.BRANCH_NAME} -B
        // """

        // // Notify to Slack
        // sh label: 'Notification: Starting Jenkins Pipeline', script:
        // """
        //   if [ ${NOTIFICATION} = "true" ]; then
        //     bash cicd/script/slack \
        //     -h ${HOOKS} \
        //     -c jenkins \
        //     -u "${GIT_NAME} is STARTING" \
        //     -i rocket \
        //     -C ${ALWAYS} \
        //     -T "CICD ${env.BRANCH_NAME}" \
        //     -m "${env.JOB_NAME} , Job No #${env.BUILD_NUMBER} ==> ${env.BUILD_URL} \
        //       \ncommit (${GIT_HASH}) by ${AUTHOR_NAME}, ${AUTHOR_DATE_RELATIVE} (${GIT_SUBJECT})"
        //   fi
        // """
      }
    }

    stage("BUILD") {
      steps {
        script {
        //   FAILED_STAGE=env.STAGE_NAME
          echo "BUILD"
        }
        // docker build --build-arg PROJECT=zdac_module --build-arg PORT=3535 -t zdac_module:${GIT_TAG} -f Dockerfile .
        sh label: 'Building Script', script:
        """
        docker build -t dhutapratama/privy-aczd-module:latest -f Dockerfile .
        """


        // sh label: 'STEP BUILD', script:
        // """
        //   make build -B
        // """
      }
    }

    // stage("TEST") {
    //   steps {
    //     script {
    //       FAILED_STAGE=env.STAGE_NAME
    //       echo "TEST"
    //     }

    //     sh label: 'STEP TEST', script:
    //     """
    //       make test -B
    //     """
    //   }
    // }

    // stage("RELEASE") {
    //   steps {
    //     script {
    //       FAILED_STAGE=env.STAGE_NAME
    //       echo "RELEASE"
    //     }

    //     sh label: 'STEP RELEASE', script:
    //     """
    //       make release -B
    //     """
    //   }
    // }

    // stage("DEPLOYMENT") {
    //   steps {
    //     script {
    //       FAILED_STAGE=env.STAGE_NAME
    //       echo "DEPLOYMENT"
    //     }

    //     sh label: 'STEP DEPLOYMENT', script:
    //     """
    //       make deploy -B
    //     """
    //   }
    // }

    // stage("FINISING") {
    //   steps {
    //     script {
    //       FAILED_STAGE=env.STAGE_NAME
    //       echo "FINISING"

    //       publishHTML (target : [allowMissing: false,
    //       alwaysLinkToLastBuild: true,
    //       keepAll: true,
    //       reportDir: 'cicd',
    //       reportFiles: 'index.html',
    //       reportName: 'My Reports',
    //       reportTitles: 'The Report'])
    //     }

    //     sh label: 'INFO', script:
    //     """
    //       make info -B
    //     """

    //     sh label: 'STEP FINISING', script:
    //     """
    //       echo "PASS"
    //     """
    //   }
    // }
  }

//   post {
//     failure {
//       sh label: 'notif failure', script:
//       """
//         if [ ${NOTIFICATION} = "true" ]; then
//           bash cicd/script/slack \
//           -h ${HOOKS} \
//           -c jenkins \
//           -u "${GIT_NAME} is FAILURE" \
//           -i fire \
//           -C ${FAILURE} \
//           -T "CICD ${env.BRANCH_NAME}" \
//           -m "STEP ==> ${FAILED_STAGE}  \
//           \n ${env.JOB_NAME} , Job No #${env.BUILD_NUMBER} is FAILURE ==> ${env.BUILD_URL}"
//         fi
//       """
//     }

//     success {
//      sh label: 'notif success', script:
//       """
//         if [ ${NOTIFICATION} = "true" ]; then
//           bash cicd/script/slack \
//           -h ${HOOKS} \
//           -c jenkins \
//           -u "${GIT_NAME} is SUCCESS" \
//           -i partying_face \
//           -C ${SUCCESS} \
//           -T "CICD ${env.BRANCH_NAME}" \
//           -m "${env.JOB_NAME} , Job No #${env.BUILD_NUMBER} is SUCCESS"
//         fi
//       """
//     }
//   }
}
