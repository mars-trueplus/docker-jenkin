pipeline {
    agent any
    stages {
        stage('Something always wrong, but true') {
            agent {
                docker {
                    image 'marstrueplus/apache2-php7.0.14:v1'
                }
            }
            steps {
                sh 'ls -lah /'
                sh 'pwd'
                sh 'ls -lah /home'
                sh 'apache2 -V'
                sh 'php -v'
            }
        }
    }
}