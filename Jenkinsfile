pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                echo 'Building application...'
            }
        }
        stage('Test') {
            steps {
                echo 'Testing application...'
            }
        }
        stage('Deploy') {
            steps {
                echo 'Deploying application...'
            // Example: sh 'scp target/app.war user@server:/opt/tomcat/webapps/'
            }
        }
    }
}
