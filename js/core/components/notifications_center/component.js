import React, { Component, Fragment } from 'react';
import ReactDOM from 'react-dom';
import { connect } from "react-redux";
import { actions, types } from './reducer';
import { getNotifications, setNotificationsRead, setAllNotificationsRead, dismissNotifications } from './api';

let toRead = [];

class Dropdown extends Component {
  render() {
    return (
      <div className='cc-dropdown' {...this.props}>
        {this.props.children}
      </div>
    );
  }
}

class DropdownTitle extends Component {
  render() {
    return (
      <div className='cc-dropdown__title-wrapper' {...this.props}>
        <span className='cc-dropdown__title'>{this.props.text}</span>

        {(this.props.children)
          ? <div className='cc-dropdown__title-buttons'>
            {this.props.children}
          </div>
          : ''
        }
      </div>
    );
  }
}

class ReadAllButton extends Component {
  render() {
    return (
      <button
        className="cc-read-all-button btn btn-text"
        onMouseUp={this.props.onMouseUp}
        onClick={this.props.onClick}
      >{this.props.text}</button>
    );
  }
}

class TabsBox extends Component {
  render() {
    return (
      <div className='cc-tabs-box' {...this.props}>
        {this.props.children}
      </div>
    );
  }
}

class TabsButton extends Component {
  render() {
    return (
      <div className='cc-tabs-button' {...this.props}>
        <span
          className={`cc-tabs-button--inner ${
            this.props.active == 'active' ? 'cc-tabs-button--inner--active' : ''
            } ${
            this.props.children != undefined ? 'cc-tabs-button--inner--has-children' : ''
            }`}
        >
          <span className="cc-tabs-button--inner-text">
            {this.props.text} <span className='cc-tabs-button-after'>{this.props.children}</span>
          </span>
        </span>
      </div>
    );
  }
}

class NotificationsBox extends Component {
  componentDidUpdate() {
    let elm = ReactDOM.findDOMNode(this);
    if (elm.scrollHeight == elm.clientHeight && elm.clientHeight != 0) {
      this.props.onBottom();
    }
  }

  render() {
    let handleScroll = (e) => {

      const bottom = Math.round(e.target.scrollHeight) - Math.round(e.target.scrollTop) === Math.round(e.target.clientHeight);

      if (bottom) {
        this.props.onBottom();
      }
    }

    return (
      <div
        onScroll={handleScroll}
        className='cc-notifications'
      >
        {this.props.children}
      </div>
    );
  }
}

class Collapsable extends Component {
  constructor(props) {
    super(props);
    this.rootRef = React.createRef();
    this.state = { collapse: false, process: false };
  }

  componentDidMount() {
    let elm = this.rootRef.current;
    if (elm.offsetHeight > this.props.at) {
      this.setState({ process: true, collapse: true });
    }
  }

  render() {
    return (
      <Fragment>
        <span className={this.state.collapse ? 'cc-collapse--enable' : 'cc-collapse--disable'} ref={this.rootRef}>
          {this.props.children}
        </span>
        {this.state.process && (
          <button
            className="cc-enable-collapse btn btn-text"
            onMouseUp={(e) => {
              e.stopPropagation();
              e.preventDefault();
              this.setState({ collapse: !this.state.collapse });
            }}
            onClick={(e) => {
              e.stopPropagation();
              e.preventDefault();
            }}
          >{this.state.collapse ? this.props.text.showMore : this.props.text.showLess}</button>
        )}
      </Fragment>
    );
  }
}

class Notification extends Component {
  render() {
    return (
      <div
        onMouseUp={this.props.onMouseUp}
        className={`cc-notification ${this.props.notification.is_read ? 'cc-notification--read' : ''} ${this.props.notification.pinned ? 'cc-notification--pinned' : ''} ${this.props.notification.action_url && this.props.notification.action_url.length ? 'cc-cursor' : ''} `}
      >
        <span>
          <b>{this.props.notification.title}</b>
        </span>
        <span className="pull-right cc-hide cc-datetime">
          {this.props.notification.datetime}
        </span>
        <span className="pull-right cc-delete">
          <span className="ty-icon icon-remove-sign cc-delete"></span>
        </span>

        <div style={{ paddingTop: '5px' }}>
          <Collapsable
            at={50}
            text={{
              showMore: this.props.langVars.showMore,
              showLess: this.props.langVars.showLess
            }}
          >
            <span
              dangerouslySetInnerHTML={{ __html: this.props.notification.message }}
            />
          </Collapsable>
          {this.props.children}
        </div>
      </div>
    );
  }
}

class Notifications extends Component {
  componentDidMount() {
    setInterval(() => {
      if (!toRead.length) {
        return;
      }

      this.readNotificationsByIds(toRead, () => {
        toRead = [];
      });

      this.props.dispatch({ type: actions.RECALC_UNREAD });
    }, 500);
  }

  appendToRead(notificationId) {
    toRead.push(notificationId);
  }

  loadNextPage() {
    const { dispatch, state } = this.props;

    const [currentSection] = state.sections.filter(s => s.section === state.selectedSectionId);
    let notificationsInSection = state.notifications.filter(n => n.section === state.selectedSectionId);
    notificationsInSection = notificationsInSection.length;

    // do not load for current section, cause current section is filled
    if (notificationsInSection == 0 || notificationsInSection == currentSection.notifications_count) {
      return;
    }

    getNotifications({
      items_per_page: state.fetchPerPage,
      page: state.fetchPage + 1
    })
      .catch(err => {
        console.log(err);
      })
      .then(payload => {
        dispatch({ type: actions.INCREMENT_PAGE });
        dispatch({ type: actions.INCREMENT_DATA, payload });
      })
      .then(() => {
        dispatch({
          type: actions.RECALC_UNREAD
        });
      });
  }

  updateCurrentSection(selectedSectionId) {
    const { dispatch } = this.props;

    dispatch({
      type: actions.SELECT_SECTION,
      payload: { selectedSectionId }
    });
  }

  readAllNotifications(cb) {
    const { state, dispatch } = this.props;

    setAllNotificationsRead()
      .then(({ result = false }) => {
        if (result) {
          
          dispatch({
            type: actions.SET_ALL_READ,
          });
        }
      })
      .catch(err => {
        console.log(err);
      })
      .then(() => {
        getNotifications({
          items_per_page: state.fetchPerPage,
          page: state.fetchPage
        })
          .catch(err => {
            console.log(err);
          })
          .then(payload => {
            dispatch({ type: actions.UPDATE_SECTIONS, payload });
          });

        if (cb) {
          cb();
        }
      });
  }

  readNotificationsByIds(unreadNotifications, cb) {
    const { state, dispatch } = this.props;

    setNotificationsRead(unreadNotifications)
      .then(({ result = false }) => {
        if (result) {
          dispatch({
            type: actions.SET_READ,
            payload: { ids: unreadNotifications }
          });
        }
      })
      .catch(err => {
        console.log(err);
      })
      .then(() => {
        getNotifications({
          items_per_page: state.fetchPerPage,
          page: state.fetchPage
        })
          .catch(err => {
            console.log(err);
          })
          .then(payload => {
            dispatch({ type: actions.UPDATE_SECTIONS, payload });
          });

        if (cb) {
          cb();
        }
      });
  }

  renderTabsBox() {
    const { state, dispatch } = this.props;

    let sections = state.sections.filter(
      section => section.section != types.sections.ALL
    ).filter(
      section => state.notifications.filter(
        n => (n.section == section.section) && (!n.hidden || false)
      ).length ? true : false
    );

    if (sections.length == 1 && state.selectedSectionId != sections[0].section) {
      dispatch({
        type: 'MERGE_DATA',
        payload: {
          sections: sections
        }
      })
      this.updateCurrentSection(sections[0].section);
    }

    return (
      <TabsBox>
        {state.sections.map(
          section => {
            let props = {
              key: section.section,
              text: section.section_name,
              active: section.section == state.selectedSectionId ? 'active' : undefined,
              onMouseDown: () => {
                this.updateCurrentSection(section.section);
              }
            },
            visibleNotifications = state.notifications.filter(
              n => (!n.hidden || false) && (n.section === section.section)
            ).length;

            if ((!visibleNotifications || !section.notifications_count) && (section.section != types.sections.ALL)) {
              return null;
            }

            return (
              section.unread_notifications_count && !(section.section == types.sections.ALL)
                ? <TabsButton {...props}  >{section.unread_notifications_count}</TabsButton>
                : <TabsButton {...props} />
            );
          }
        )}
      </TabsBox>
    );
  }

  renderNotificationsBox() {
    const { state, dispatch } = this.props,
      filterNotifications = (
        notification => {
          return (
            (
              (notification.section === state.selectedSectionId)
              ||
              (state.selectedSectionId === types.sections.ALL)
            )
            &&
            (!notification.hidden || false)
          );
        }
      );

    return (
      <NotificationsBox onBottom={() => {
        this.loadNextPage();
      }}>
        {state.notifications
          .filter(filterNotifications)
          .map((notification, i) => {
            if (!notification.is_read) {
              if (!(notification.action_url && notification.action_url.length)) {
                this.appendToRead(notification.notification_id);
              }
            }

            return (
              <Notification
                key={notification.notification_id}
                notification={notification}
                langVars={this.props.langVars}
                onMouseUp={
                  (e) => {
                    e.stopPropagation();
                    e.preventDefault();

                    if ($(e.target).hasClass('cc-delete')) {
                      dismissNotifications([notification.notification_id])
                        .then(({ result }) => {
                          if (result) {

                            if (!notification.is_read) {
                              this.appendToRead(notification.notification_id);
                            }

                            dispatch({
                              type: actions.DISMISS_NOTIFICATION,
                              payload: {
                                notificationsIds: [notification.notification_id]
                              }
                            });
                          }
                        });
                    } else {
                      if (notification.action_url && notification.action_url.length) {
                        if (!notification.is_read) {
                          setNotificationsRead([notification.notification_id])
                            .then(() => {
                              _open();
                            })
                        } else {
                          _open();
                        }

                        function _open() {
                          if (notification.action_url.includes(location.hostname)) {
                            location.href = notification.action_url;
                          } else {
                            window.open(notification.action_url, '_blank');
                          }
                        }
                      }
                    }
                  }
                }
              />
            );
          })
        }
      </NotificationsBox>
    )
  }

  render() {
    const { state, langVars } = this.props,
      filterNotifications = (
        notification => {
          return (
            (
              (notification.section === state.selectedSectionId)
              ||
              (state.selectedSectionId === types.sections.ALL)
            )
            &&
            (!notification.hidden || false)
          );
        }
      ),
      filtered = state.notifications.filter(filterNotifications),
      allNotifications = state.notifications.filter(
        n => (n.hidden || false) ? false : true
      ).length;

    return (
      <Dropdown>
        <DropdownTitle text={langVars.notifications}>
          <ReadAllButton
            text={langVars.markAllAsRead}
            onMouseUp={(e) => {
              e.stopPropagation();
              e.preventDefault();
    
              this.readAllNotifications();
            }}
            onClick={(e) => {
              e.stopPropagation();
              e.preventDefault();
            }}
          />
        </DropdownTitle>

        {((state.sections || []).length || filtered.length) && allNotifications
          ? this.renderTabsBox()
          : ''
        }

        {filtered.length
          ? this.renderNotificationsBox()
          : state.loaded
            ? <div className="cc-all-read"> <div className="cc-all-read--inner"> {langVars.noData} </div> </div>
            : <div className="cc-all-read"> <div className="cc-all-read--inner"> {langVars.loading} </div> </div>
        }
      </Dropdown>
    );
  }
}

export const NotificationsCenter = connect(
  // mapStateToProps
  (state, ownProps) => {
    return {
      state
    };
  },
  // mapDispatchToProps
  (dispatch, ownProps) => {
    return {
      dispatch
    }
  }
)(Notifications);

class Counter extends Component {
  render() {
    return (
      this.props.state.unreadCount
        ? <div className="cc-notify-counter-content">{this.props.state.unreadCount}</div>
        : ''
    );
  }
}

export const NotificationsCenterCounter = connect(
  // mapStateToProps
  (state, ownProps) => {
    return {
      state
    };
  },
  // mapDispatchToProps
  (dispatch, ownProps) => {
    return {
      dispatch
    }
  }
)(Counter);

class Marker extends Component {
  render() {
    return (
      this.props.state.unreadCount
        ? <div className="cc-notify-marker-content"></div>
        : ''
    );
  }
}

export const NotificationsMark = connect(
  // mapStateToProps
  (state, ownProps) => {
    return {
      state
    };
  },
  // mapDispatchToProps
  (dispatch, ownProps) => {
    return {
      dispatch
    }
  }
)(Marker);