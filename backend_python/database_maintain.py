#coding: utf-8

"""
扫描数据库内的mail脚本, 并:

1. 对正在途中的mail的状态进行更新
2. 对到达指定寄达时间的mail进行发送并更新状态
"""
import sys

import MySQLdb
import json
import datetime
import time
import sched
import logging
import logging.handlers

import settings
from settings import MINIMUM_TIME_GRANULARITY
from email_helper import EmailHandler

DB_config = {
    "host": "127.0.0.1",
    "user": "root",
    "passwd": "",
    "db": "manji",
    "charset": "utf8"
}

DB_attri = {
    "table_name": ["t_mail", "t_mail_state"],
    "t_mail": ["mail_id", "user_id", "address_id", "pub_time", "is_read", "arrive_time", "mail_content", "email"],
    "t_mail_state": [ "mstate_id", "poster_id", "start_time", "end_time", "description", "mood", "mood_time", "mail_id"],
}

# create logger
logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)

# create console handler and set level to debug
ch = logging.StreamHandler()
ch.setLevel(logging.DEBUG)

# create formatter
formatter = logging.Formatter('[%(asctime)s] p%(process)s {%(pathname)s:%(lineno)d} %(levelname)s - %(message)s','%m-%d %H:%M:%S')
# add formatter to ch
ch.setFormatter(formatter)

# add ch to logger
logger.addHandler(ch)

# ==========================================================


def connect_database():
    """连接数据库"""
    db = MySQLdb.connect(**DB_config)
    cursor = db.cursor()
    return db, cursor


def disconnect_database(db):
    """关闭数据库连接"""
    db.close()


def get_cur_time():
    return int(round(time.mktime(datetime.datetime.now().timetuple())))


def dbdata_to_dict(dbdata, attri_name):
    """type of dbdtat: [[string, string], [], ..., []]"""
    data = []
    for dblist in dbdata:
        item = {}
        for i in range(len(attri_name)):
            item[attri_name[i]] = dblist[i]
        data.append(item)
    return data


def get_unsend_mails(cursor, cur_time):
    """获取所有未发送的mail"""
    # cur_time = int(round(time.mktime(datetime.datetime.now().timetuple())))

    sql = """SELECT t_mail.mail_id as mail_id, t_mail.arrive_time as arrive_time FROM t_mail WHERE {} <= t_mail.arrive_time""".format(cur_time)
    cursor.execute(sql)
    # mails = cursor.fetchall()
    mails_sql = cursor.fetchall()
    mails = [{"mail_id": mail_sql[0], "arrive_time": mail_sql[1]} for mail_sql in mails_sql]
    return mails


def form_email_content(mail_content, mail_states):
    text = ""
    html = mail_content
    for state in mail_states:
        html = html + state['description']
        if (state['mood'] != None):
            html = html + '<p>' + state['mood'] + '-- ' + str(state['mood_time']) + '</p>'
    image_url_list = []
    return text, html, image_url_list


def parse_mail(cursor, mail_id):
    # 获取mail信息
    mail_need = settings.DB_attri['t_mail']
    sql = """SELECT {}  FROM t_mail WHERE mail_id={}""".format(','.join(mail_need), mail_id)
    cursor.execute(sql)
    mails_sql = cursor.fetchall()
    mail = dbdata_to_dict(mails_sql, mail_need)[0]

    logger.info("parse mail: {}".format(mail))

    # 获取mail的状态(们), 并按时间顺序排序
    mail_state_need = settings.DB_attri['t_mail_state']
    sql = """SELECT {} FROM t_mail_state WHERE mail_id={} ORDER BY start_time DESC""".format(
        ','.join(mail_state_need), mail_id)
    cursor.execute(sql)
    mail_states_sql = cursor.fetchall()
    mail_states = dbdata_to_dict(mail_states_sql, mail_state_need)
    
    logger.info("parse state: {}".format(mail_states))

    email_to = mail['email']
    subject = settings.standard_email_subject
    text, html, image_url_list = form_email_content(mail['mail_content'], mail_states)
    return email_to, subject, text, html, image_url_list


def send_mail(cursor, mail_id, smtp_password):
    email_to, subject, text, html, image_url_list = parse_mail(cursor, mail_id)
    # mail寄达
    try:
        email_sender = EmailHandler(
            settings.smtp_server, settings.smtp_username,
            smtp_password)

        email_sender.send_email(
            settings.smtp_username, [email_to],
            subject, text,
            html, image_url_list)
        logger.info("send a mail succeed. mail_id={}, mail_to={}".format(
            mail_id, email_to))
        return True
    except Exception as e:
        logger.error("{}. send mail failed. mail_id={}, mail_to=[{}]".format(
            str(e), mail_id, email_to))
        return False


def update_unsend_mails(cursor, mails, cur_time, smtp_password):
    num_send_out = 0

    """更新未发送mail的状态"""
    for mail in mails:
        mail_id = mail['mail_id']
        arrive_time = mail['arrive_time']
        if (arrive_time <= cur_time and arrive_time + MINIMUM_TIME_GRANULARITY > cur_time):
        # if (arrive_time + MINIMUM_TIME_GRANULARITY > cur_time):
            if send_mail(cursor, mail_id, smtp_password):
                num_send_out += 1

    logger.info("sending out {} mail(s).".format(num_send_out))


def maintain_mails(smtp_password):
    try:
        db, cursor = connect_database()
        logger.info("connect to database succeed.")

        cur_time = get_cur_time()
        mails = get_unsend_mails(cursor, cur_time)
        update_unsend_mails(cursor, mails, cur_time, smtp_password)

        disconnect_database(db)
        logger.info("disconnect with database.")
    except Exception as e:
        logger.error("maintain mails failed. {}".format(str(e)))


def main(argv):
    smtp_password = argv[0]
    while (True):
        logger.info("starting scan mails from database...")
        maintain_mails(smtp_password)
        time.sleep(MINIMUM_TIME_GRANULARITY)


if __name__ == "__main__":
    # test()
    main(sys.argv[1:])
