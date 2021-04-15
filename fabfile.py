"""
Magento 2 Fab File

This file will take care of setting your magento project up and deploying it to
your beta and production servers.

To view how to use any of these tasks, run fab -d COMMAND
"""
from __future__ import with_statement
import os
import calendar
import time
import datetime
from fabric.api import *
from fabric.contrib.project import rsync_project
from fabric.contrib.console import confirm
from fabric.contrib.files import exists
from fabric.colors import cyan

# convert timestamp to unix epoch format
epoch_time = calendar.timegm(time.gmtime())

# setup our variables
sshuser = 'webadmin'  # the user you use to ssh into your server
web_root = '/home/webadmin/sites/molekule.com'  # the web root of your website
web_folder = web_root + '/public_html'  # the document root of your website
releases_folder = web_root + '/releases'  # this folder serves as a backup folder to quickly roll back to a previous release incase of emergency
shared_folder = web_root + '/common'  # this is a folder which contains all the files and folders thats not in your source versioning
repo_folder = web_root + '/repo'  # we checkout our repo to this folder and from here rsync to our release folder
media_folder = shared_folder + '/media'  # youre media folder or mount
pub_media_folder = shared_folder + '/pub/media'  # youre pub media folder or mount
ca_en_folder = shared_folder + '/pub/ca-en'  # youre pub canadian folder or mount
go_folder = shared_folder + '/pub/go'  # the go folder

#env.key_filename = '/Users/campbell_vonnda/.ssh/mlk_admin.pem'  # keyfile incase you use one - if you use a public key simply comment this
git_repo = 'git@bitbucket.org:vonnda/mlk.git'  # git repo url
notice_prefix = cyan('\n=> DEPLOY NOTICE ::')  # deploy notice prefix
ts = time.time()
stamp = datetime.datetime.fromtimestamp(ts).strftime('%Y%m%d-%H%M')
keep = '3'  # number of backups / releases to keep in your releases folder
permissions_user = 'webadmin'  # web server user
permissions_group = 'webadmin'  # web server group

# MLK-M2DEV environment
m2dev_admin =  sshuser + '@' + 'm2dev-admin.molekule.com'
m2dev_node_fe1 = sshuser + '@' + '35.183.126.169'
m2dev_node_fe2 = sshuser + '@' + '35.183.177.108'

# PROD-CA environment
mlk_prod_adm_ca = sshuser + '@' + 'ec2-35-183-159-100.ca-central-1.compute.amazonaws.com'
mlk_prod_fe1_ca = sshuser + '@' + '15.222.3.128'
mlk_prod_fe2_ca = sshuser + '@' + '35.183.132.230'
mlk_prod_fe3_ca = sshuser + '@' + '99.79.195.114'
mlk_prod_fe4_ca = sshuser + '@' + '35.182.177.209'
mlk_prod_fe5_ca = sshuser + '@' + '35.182.174.218'

# STAGING-ANATTA environment
mlk_staging_anatta = sshuser + '@' + '35.183.111.76'

# STAGING-VONNDA-NET environment
mlk_staging_vonnda_net = sshuser + '@' + 'mlk-staging.vonnda.net'

# define our host and infrastructure roles
env.roledefs = {
    'mlk-anatta': {
            'hosts': [
                mlk_staging_anatta,
            ],
    },
    'mlk-staging': {
            'hosts': [
                mlk_staging_vonnda_net,
            ],
    },
    'mlk-m2dev': {
            'hosts': [
                m2dev_admin,
                m2dev_node_fe1,
                m2dev_node_fe2,
            ],
    },
    'mlk-prod-ca': {
            'hosts': [
                mlk_prod_adm_ca,
                mlk_prod_fe1_ca,
                mlk_prod_fe2_ca,
                mlk_prod_fe3_ca,
                mlk_prod_fe4_ca,
                mlk_prod_fe5_ca,
            ],
    },
    'mlk-prod-adm-ca': [mlk_prod_adm_ca],
    'mlk-prod-fe-ca': {
            'hosts': [
                mlk_prod_fe1_ca,
                mlk_prod_fe2_ca,
                mlk_prod_fe3_ca,
                mlk_prod_fe4_ca,
                mlk_prod_fe5_ca,
            ],
    },
}

# default role will be stage
env.roledefs['all'] = [h for r in env.roledefs.values() for h in r]

@parallel
def deploy(branch='master', tag=''):
    """
    # deploy
    # deploy a branch (for now) to an environment
    """
    if 'mlk-anatta' or 'mlk-staging' or 'mlk-m2dev' in env.effective_roles:
        run('rm -Rf ' + repo_folder)

    with settings(warn_only=True):
        with hide('stdout', 'stderr', 'warnings'):
            print '%s check if our repo directory exists' % notice_prefix
            if run("test -d %s" % repo_folder).failed:
                print '%s %s does not exist..cloning' % (notice_prefix, repo_folder)
                run("git clone " + git_repo + " %s" % repo_folder)
    with cd(repo_folder):
        print '%s %s exists..deploying' % (notice_prefix, repo_folder)
        print '%s current directory %s' % (notice_prefix, run('pwd'))
        if 'mlk-anatta' or 'mlk-staging' or 'mlk-m2dev' in env.effective_roles:
            run('git checkout ' + branch)
        else:
            run('git fetch')
            run('git pull -f origin ' + branch)

    print '%s exporting repo into release with timestamp...' % notice_prefix
    run('rsync -rlpgoD --delete --exclude=\'.git\' ' + repo_folder + '/ ' + releases_folder + '/' + stamp + ' -SXl')
    print '%s rysnc done!' % notice_prefix

    run('touch ' + shared_folder + '/offline/maintenance.flag')
    symlink()
    link_to_web()
    magento()
    restartphp()
    cleanup()
    print '%s deploy of %s complete' % (notice_prefix, branch)

@parallel
def enablemaint():
    run('touch ' + shared_folder + '/offline/maintenance.flag')

@parallel
def disablemaint():
    run('rm -f ' + shared_folder + '/offline/maintenance.flag')

@parallel
def cronstart():
    if env.host_string == mlk_staging_anatta or env.host_string == mlk_staging_vonnda_net or env.host_string == m2dev_admin or env.host_string == mlk_prod_adm_ca:
        run('sudo /usr/sbin/service cron start')

@parallel
def cronstop():
    if env.host_string == mlk_staging_anatta or env.host_string == mlk_staging_vonnda_net or env.host_string == m2dev_admin or env.host_string == mlk_prod_adm_ca:
        run('sudo /usr/sbin/service cron stop')

@parallel
def symlink():
    """
    # create symlinks to media, var etc
    """
    print '%s linking to shared files and folders' % notice_prefix
    # symlink media
    with settings(warn_only=True):
        with hide('stdout', 'stderr', 'warnings'):
            # Create symlinks for var/log folder.
            print '%s Create symlinks for var/log folder' % notice_prefix
            if run("test -d %s" % releases_folder + '/' + stamp + '/var/log').failed:
                print '%s %s/%s/var/log not found' % (notice_prefix, releases_folder, stamp)
                run('ln -sf ' + shared_folder + '/var/log ' + releases_folder + '/' + stamp + '/var/log')
            else:
                print '%s %s/%s/var/log folder exists..deleting' % (notice_prefix, releases_folder, stamp)
                run('rm -rf ' + releases_folder + '/' + stamp + '/var/log')
                run('ln -sf ' + shared_folder + '/var/log ' + releases_folder + '/' + stamp + '/var/log')

            # Create symlinks for var/report folder.
            print '%s Create symlinks for var/report folder' % notice_prefix
            if run("test -d %s" % releases_folder + '/' + stamp + '/var/report').failed:
                print '%s %s/%s/var/report not found' % (notice_prefix, releases_folder, stamp)
                run('ln -sf ' + shared_folder + '/var/report ' + releases_folder + '/' + stamp + '/var/report')
            else:
                print '%s %s/%s/var/report folder exists..deleting' % (notice_prefix, releases_folder, stamp)
                run('rm -rf ' + releases_folder + '/' + stamp + '/var/report')
                run('ln -sf ' + shared_folder + '/var/report ' + releases_folder + '/' + stamp + '/var/report')

            # Check for media folder in pub directory.
            print '%s check if media exists in pub folder' % notice_prefix
            if run("test -d %s" % releases_folder + '/' + stamp + '/pub/media').failed:
                print '%s %s/%s/pub/media not found' % (notice_prefix, releases_folder, stamp)
                run('cd ' + releases_folder + '/' + stamp + '/pub/ && ln -sf ' + pub_media_folder + '/')
            else:
                print '%s %s/%s/media exist..deleting' % (notice_prefix, releases_folder, stamp)
                run('rm -rf ' + releases_folder + '/' + stamp + '/pub/media')
                run('cd ' + releases_folder + '/' + stamp + '/pub/ && ln -sf ' + pub_media_folder + '/')

            # Check for images folder in pub.
            print '%s check if our images folder file exists within pub' % notice_prefix
            if run("test -d %s" % releases_folder + '/' + stamp + '/pub/images').failed:
                print '%s %s/%s/pub/images not found' % (notice_prefix, releases_folder, stamp)
                run('ln -sf ' + shared_folder + '/pub/images ' + releases_folder + '/' + stamp + '/pub/images')
            else:
                print '%s %s/%s/pub/images folder exists..deleting' % (notice_prefix, releases_folder, stamp)
                run('rm -rf ' + releases_folder + '/' + stamp + '/pub/images')
                run('ln -sf ' + shared_folder + '/pub/images ' + releases_folder + '/' + stamp + '/pub/images')

            # Check for robots.txt file.
            print '%s check if our robots.txt file exists within web' % notice_prefix
            if run("test -f %s" % releases_folder + '/' + stamp + '/pub/robots.txt').failed:
                print '%s %s/%s/pub/robots.txt not found' % (notice_prefix, releases_folder, stamp)
                run('ln -sf ' + shared_folder + '/robots.txt ' + releases_folder + '/' + stamp + '/pub/robots.txt')
            else:
                print '%s %s/%s/robots.txt exists..deleting' % (notice_prefix, releases_folder, stamp)
                run('rm -rf ' + releases_folder + '/' + stamp + '/pub/robots.txt')
                run('ln -sf ' + shared_folder + '/robots.txt ' + releases_folder + '/' + stamp + '/pub/robots.txt')

            # Check for sitemap.xml file.
            print '%s check if our sitemap.xml file exists within web' % notice_prefix
            if run("test -f %s" % releases_folder + '/' + stamp + '/pub/sitemap.xml').failed:
                print '%s %s/%s/pub/sitemap.xml not found' % (notice_prefix, releases_folder, stamp)
                run('ln -sf ' + shared_folder + '/sitemap.xml ' + releases_folder + '/' + stamp + '/pub/sitemap.xml')
            else:
                print '%s %s/%s/sitemap.xml exists..deleting' % (notice_prefix, releases_folder, stamp)
                run('rm -rf ' + releases_folder + '/' + stamp + '/pub/sitemap.xml')
                run('ln -sf ' + shared_folder + '/sitemap.xml ' + releases_folder + '/' + stamp + '/pub/sitemap.xml')

            # Check for env.php file.
            print '%s check if our env.php file exists within web' % notice_prefix
            run('cd ' + releases_folder + '/' + stamp + '/app/etc/ && cp ' + shared_folder +'/env.php ./')

            # Check deployment environment, and use the /go/ route if we are in staging or dev
            if 'mlk-staging' or 'mlk-testing' or 'mlk-m2dev' or 'mlk-prod-ca' in env.effective_roles:
                print '%s check if go exists in pub folder' % notice_prefix
                if run("test -d %s" % releases_folder + '/' + stamp + '/pub/go').failed:
                    print '%s %s/%s/pub/go not found' % (notice_prefix, releases_folder, stamp)
                    run('cd ' + releases_folder + '/' + stamp + '/pub/ && ln -sf ' + go_folder + '/')
                else:
                    print '%s %s/%s/go exist..deleting' % (notice_prefix, releases_folder, stamp)
                    run('rm -rf ' + releases_folder + '/' + stamp + '/pub/go')
                    run('cd ' + releases_folder + '/' + stamp + '/pub/ && ln -sf ' + go_folder + '/')
            else:
                # We are not in a staging or dev environment, use the normal ca-en store instead of /go/
                # Check for Canadian Store folder in pub directory.
                print '%s check if ca-en exists in pub folder' % notice_prefix
                if run("test -d %s" % releases_folder + '/' + stamp + '/pub/ca-en').failed:
                    print '%s %s/%s/pub/ca-en not found' % (notice_prefix, releases_folder, stamp)
                    run('cd ' + releases_folder + '/' + stamp + '/pub/ && ln -sf ' + ca_en_folder + '/')
                else:
                    print '%s %s/%s/ca-en exist..deleting' % (notice_prefix, releases_folder, stamp)
                    run('rm -rf ' + releases_folder + '/' + stamp + '/pub/ca-en')
                    run('cd ' + releases_folder + '/' + stamp + '/pub/ && ln -sf ' + ca_en_folder + '/')

@parallel
def link_to_web():
    """
    # create symlink to web which is our document root, defined at the webserver level
    """
    print 'linking to web'
    with settings(warn_only=True):
            with hide('stdout', 'stderr', 'warnings'):
                print '%s check if our web directory exists' % notice_prefix
                if run("test -d %s" % web_folder).failed:
                    print '%s %s not found' % (notice_prefix, web_folder)
                    print '%s linking to web folder' % notice_prefix
                    run('ln -s ' + releases_folder + '/' + stamp + ' ' + web_folder)
                else:
                    run('rm ' + web_folder)
                    run('ln -s ' + releases_folder + '/' + stamp + ' ' + web_folder)

@parallel
def magento():
    """
    # Run Magento specific commands. composer, npm, etc.
    """
    print '%s Running magento 2 deployment commands.' % notice_prefix
    run('cd ' + web_folder + '/ && composer install')
    print '%s apply vendor patch MDVA-12304_EE_2.2.5_v1.composer' % notice_prefix
    run('cd ' + web_folder + '/ && patch -p1 < MDVA-12304_EE_2.2.5_v1.composer.patch')

    if env.host_string == mlk_staging_anatta or env.host_string == mlk_staging_vonnda_net or env.host_string == m2dev_admin or env.host_string == mlk_prod_adm_ca:
        run('cd ' + web_folder + '/bin && php magento setup:upgrade')

    run('cd ' + web_folder + '/bin && php magento setup:di:compile')
    # run('rm -rf ' + web_folder + '/var/view_prepocessed/pub/')
    run('cd ' + web_folder + '/bin && HTTPS="on" php magento setup:static-content:deploy --content-version ' + str(epoch_time) + ' --exclude-theme Magento/luma -j 0')
    post_deploy_updates()
    run('cd ' + web_folder + '/bin && php magento cache:flush')
    run('rm -f ' + shared_folder + '/offline/maintenance.flag')

@parallel
def post_deploy_updates():
    """
    # create a symlink to common/pub/static/_cache/
    """
    with settings(warn_only=True):
        with hide('stdout', 'stderr', 'warnings'):
            print '%s Create a symlink for the pub/static/_cache folder.' % notice_prefix
            if run("test -d %s" % releases_folder + '/' + stamp + '/pub/static/_cache').failed:
                print '%s %s/%s/pub/static/_cache not found' % (notice_prefix, releases_folder, stamp)
                run('ln -sf ' + shared_folder + '/pub/static/_cache ' + releases_folder + '/' + stamp + '/pub/static/_cache')
            else:
                print '%s %s/%s/pub/static/_cache folder exists..deleting' % (notice_prefix, releases_folder, stamp)
                run('rm -rf ' + releases_folder + '/' + stamp + '/pub/static/_cache')
                run('ln -sf ' + shared_folder + '/pub/static/_cache ' + releases_folder + '/' + stamp + '/pub/static/_cache')
            # Check for .user.ini file.
            print '%s check if public_html/pub/.user.ini file exists' % notice_prefix
            if run("test -f %s" % releases_folder + '/' + stamp + '/pub/.user.ini').failed:
                print '%s %s/%s/pub/.user.ini not found' % (notice_prefix, releases_folder, stamp)
                run('ln -sf ' + shared_folder + '/user.ini ' + releases_folder + '/' + stamp + '/pub/.user.ini')
            else:
                print '%s %s/%s/pub/.user.ini exists..deleting' % (notice_prefix, releases_folder, stamp)
                run('rm -rf ' + releases_folder + '/' + stamp + '/pub/.user.ini')
                run('ln -sf ' + shared_folder + '/user.ini ' + releases_folder + '/' + stamp + '/pub/.user.ini')
            # Check for .user.ini file.
            print '%s check if public_html/.user.ini file exists' % notice_prefix
            if run("test -f %s" % releases_folder + '/' + stamp + '/.user.ini').failed:
                print '%s %s/%s/.user.ini not found' % (notice_prefix, releases_folder, stamp)
                run('ln -sf ' + shared_folder + '/user.ini ' + releases_folder + '/' + stamp + '/.user.ini')
            else:
                print '%s %s/%s/.user.ini exists..deleting' % (notice_prefix, releases_folder, stamp)
                run('rm -rf ' + releases_folder + '/' + stamp + '/.user.ini')
                run('ln -sf ' + shared_folder + '/user.ini ' + releases_folder + '/' + stamp + '/.user.ini')

@parallel
def restartphp():
    """
    # php
    # restart php
    """
    print '%s restarting PHP' % notice_prefix
    run('sudo service php7.2-fpm restart')

@parallel
def cleanup():
    """
    # prune some releases in our releases folder
    # we keep only the last 3 releases
    """
    with settings(warn_only=True):
        with hide('stdout', 'stderr', 'warnings'):
            print '%s removing everything but the last %s releases archives' % (notice_prefix, keep)
            run('(cd ' + releases_folder + ' && rm -rf $(ls -t --color=no | tail -n +' + str(int(float(keep) + 1)) + '))')
