pipeline {
    agent any
    stages {
        stage('Something always wrong, but true') {
            agent {
                docker {
                    image 'marstrueplus/m2_apache2-php7.0.14:v1'
                }
            }
            steps {
                sh 'apache2 -V'
                sh 'php -v'
                sh 'ls -lah /etc/php7'
            }
        }
    }
}
