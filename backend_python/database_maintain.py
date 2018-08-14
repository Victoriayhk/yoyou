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
import json
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

    sql = """SELECT mail_id,arrive_time,is_send FROM t_mail WHERE {} = t_mail.arrive_time AND is_send = 0""".format(cur_time)
    # sql = """SELECT mail_id,arrive_time,is_send FROM t_mail""".format(cur_time)
    cursor.execute(sql)
    mails_sql = cursor.fetchall()
    mails = [{"mail_id": mail_sql[0], "arrive_time": mail_sql[1], "is_send": mails_sql[2]} for mail_sql in mails_sql]
    return mails


def form_email_content(mail_content, mail_states):
    text = ""
    html = ""
    image_url_list = []

    json_content = json.loads(mail_content)

    for item in json_content:
        if item['type'] == 'text':
            html = html + '<p>' + item['text'].encode('utf-8') + '</p>'
        elif item['type'] == 'image':
            html = html + '<br><img src="{}"><br>'.format(item['value'])

    for state in mail_states:
        descri = state['description'] #.encode('utf-8')
        html = html + '</p>' + descri + '</p>'
        if (state['mood'] != None):
            html = html + '<p>' + state['mood'] + '发表于 ' + str(state['mood_time']) + '</p>'

    return text, html, image_url_list


def form_email_content2(cursor, mail_id, mail_content, mail_states):
    # mail_json = json.loads(mail_content)

    cursor.execute(
        "SELECT user_id, pub_time, poster_id FROM t_mail WHERE mail_id={}".format(mail_id))
    data = cursor.fetchall()[0]
    user_id = data[0]
    pub_time = data[1]
    poster_id = data[2]

    cursor.execute(
        "SELECT user_name FROM t_user WHERE user_id={}".format(user_id))
    data = cursor.fetchall()[0]
    user_name = data[0]

    cursor.execute(
        "SELECT poster_name FROM t_poster WHERE poster_id={}".format(poster_id))
    data = cursor.fetchall()[0]
    poster_name = data[0]

    t_pub_time = time.localtime(pub_time)
    str_pub_time = time.strftime("%Y-%m-%d", t_pub_time)

    mail_json = {}
    mail_json['from'] = user_name
    mail_json['pub_time'] = str_pub_time
    mail_json['poster_name'] = poster_name

    # for state in mail_states:
    #     state_item = {}
    #     state_item['type'] = 'text'
    #     text =  '</p>' + state['description'] + '</p>'
    #     if (state['mood'] != None):
    #         mood_time = time.localtime(state['mood_time'])
    #         str_mood_time = time.strftime("%Y-%m-%d %H:%M:%S", mood_time)
    #         text = text + '<p>' + state['mood'] + '-- ' + str_mood_time + '</p>'
    #     state_item['text'] = text
    #     mail_json.append(state_item)

    return mail_json


def parse_mail(cursor, mail_id):
    # 获取mail信息
    mail_need = settings.DB_attri['t_mail']
    sql = """SELECT {}  FROM t_mail WHERE mail_id={}""".format(','.join(mail_need), mail_id)
    cursor.execute(sql)
    mails_sql = cursor.fetchall()
    try:
        mail = dbdata_to_dict(mails_sql, mail_need)[0]
    except Exception as e:
        logger.error("{}. mail_id={}, 没有找到对应的mail".format(str(e), mail_id))
        raise ValueError("rt.")

    # 获取mail的状态(们), 并按时间顺序排序
    mail_state_need = settings.DB_attri['t_mail_state']
    sql = """SELECT {} FROM t_mail_state WHERE mail_id={} ORDER BY start_time DESC""".format(
        ','.join(mail_state_need), mail_id)
    cursor.execute(sql)
    mail_states_sql = cursor.fetchall()
    mail_states = dbdata_to_dict(mail_states_sql, mail_state_need)
    
    email_to = mail['email']
    subject = settings.standard_email_subject
    text, html, image_url_list = form_email_content(mail['mail_content'], mail_states)
    return email_to, subject, text, html, image_url_list


def parse_mail2(cursor, mail_id):
    # 获取mail信息
    mail_need = settings.DB_attri['t_mail']
    sql = """SELECT {}  FROM t_mail WHERE mail_id={}""".format(','.join(mail_need), mail_id)
    cursor.execute(sql)
    mails_sql = cursor.fetchall()
    try:
        mail = dbdata_to_dict(mails_sql, mail_need)[0]
    except Exception as e:
        logger.error("{}. mail_id={}, 没有找到对应的mail".format(str(e), mail_id))
        raise ValueError("rt.")

    # 获取mail的状态(们), 并按时间顺序排序
    mail_state_need = settings.DB_attri['t_mail_state']
    sql = """SELECT {} FROM t_mail_state WHERE mail_id={} ORDER BY start_time DESC""".format(
        ','.join(mail_state_need), mail_id)
    cursor.execute(sql)
    mail_states_sql = cursor.fetchall()
    mail_states = dbdata_to_dict(mail_states_sql, mail_state_need)
    
    email_to = mail['email']
    subject = settings.standard_email_subject
    mail_json = form_email_content2(cursor, mail_id, mail['mail_content'], mail_states)
    return email_to, subject, mail_json


def send_mail(cursor, mail_id, smtp_password):
    # email_to, subject, text, html, image_url_list = parse_mail(cursor, mail_id)
    email_to, subject, mail_json = parse_mail2(cursor, mail_id)

    # 测试用
    # mail_json = [
    #     {u'text': u'0', u'type': u'text'},
    #     {u'text': u'1', u'type': u'text'},
    #     {u'value': u'http://www.baidu.com/img/bd_logo1.png', u'type': u'image'},
    #     {u'text': u'2', u'type': u'text'},
    #     {'text': u'</p>\u5df2\u7ecf\u5230Guangzhou\u4e86, \u80dc\u5229\u5c31\u5728\u773c\u524d, \u5c31\u5feb\u5230\u4e86\u5462, \u5c0f\u9a6c\u52a0\u5feb\u4e86\u524d\u8fdb\u7684\u6b65\u4f10</p>', 'type': 'text'}
    # ]

    # mail寄达
    try:
        email_sender = EmailHandler(
            settings.smtp_server, settings.smtp_username,
            smtp_password)

        email_sender.send_email2(
            settings.smtp_username, [email_to],
            subject, mail_json)

        logger.info("send a mail succeed. mail_id={}, mail_to={}".format(
            mail_id, email_to))
        
        return True
    except Exception as e:
        logger.error("{}. send mail failed. mail_id={}, mail_to=[{}]".format(
            unicode(e), mail_id, email_to))
        return False


def unsend_all_mails(db, cursor, mails, cur_time, smtp_password):
    cursor.execute("""SELECT mail_id FROM t_mail WHERE is_send=0""")
    mails_sql = cursor.fetchall()
    mail_ids = [mail[0] for mail in mails_sql]
    for mid in mail_ids:
        cursor.execute("UPDATE t_mail SET is_send=1 WHERE mail_id={}".format(mid));
    db.commit()


def update_unsend_mails(db, cursor, mails, cur_time, smtp_password):
    num_send_out = 0

    """更新未发送mail的状态"""
    for mail in mails:
        mail_id = mail['mail_id']
        arrive_time = mail['arrive_time']
        is_send = mail['is_send']
        if (arrive_time >= cur_time and is_send == 0):
            if send_mail(cursor, mail_id, smtp_password):
                cursor.execute("UPDATE t_mail SET is_send=1 WHERE mail_id={}".format(mail_id));
                num_send_out += 1

    db.commit()
    logger.info("成功发出 {} 封邮件.".format(num_send_out))


def maintain_mails(smtp_password):
    try:
        db, cursor = connect_database()
        logger.info("成功连接到数据库")

        cur_time = get_cur_time()
        mails = get_unsend_mails(cursor, cur_time)
        update_unsend_mails(db, cursor, mails, cur_time, smtp_password)

        disconnect_database(db)
        logger.info("断开与数据库的连接")
    except Exception as e:
        logger.error("扫描失败. {}".format(str(e)))


def test_send_mail(argv):
    smtp_password = argv[0]
    mail_id = argv[1]

    db, cursor = connect_database()
    logger.info("connect to database succeed.")

    cur_time = get_cur_time()
    send_mail(cursor, mail_id, smtp_password)

    disconnect_database(db)
    logger.info("disconnect with database.")


def main(argv):
    smtp_password = argv[0]

    start_time = get_cur_time()

    while (True):
        logger.info("开始扫描数据库中的mail")
        maintain_mails(smtp_password)

        next_scan_time = time.localtime(get_cur_time() + MINIMUM_TIME_GRANULARITY)
        logger.info("扫描结束. 下一轮扫描会在 {} 进行 ".format(
            time.strftime("%Y-%m-%d %H:%M:%S", next_scan_time)))
        time.sleep(MINIMUM_TIME_GRANULARITY)


if __name__ == "__main__":
    reload(sys)
    sys.setdefaultencoding('utf-8')

    assert(len(sys.argv) >= 2)
    if (sys.argv[1] == "test"):
        mail_id = sys.argv[3]
        # for mail_id in range(30, 62):
        smtp_password = sys.argv[2]
        test_send_mail([smtp_password, mail_id])
    else:
        main(sys.argv[2:])
