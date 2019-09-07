import React, { Fragment } from "react";
import ReactDOM from "react-dom";

import { ReactComponent as FormatBold } from "../assets/icons/format_bold.svg";
import { ReactComponent as FormatUnderline } from "../assets/icons/format_underline.svg";
import { ReactComponent as FormatItalic } from "../assets/icons/format_italic.svg";
import { ReactComponent as FormatLink } from "../assets/icons/format_link.svg";
import { ReactComponent as FormatH1 } from "../assets/icons/text_large.svg";
import { ReactComponent as FormatH2 } from "../assets/icons/text_small.svg";
import { ReactComponent as FormatQuote } from "../assets/icons/quote.svg";

import { ReactComponent as FormatListUl } from "../assets/icons/list_ul.svg";
import { ReactComponent as FormatList } from "../assets/icons/list.svg";

import { ReactComponent as AlignCenter } from "../assets/icons/align_center.svg";
import { ReactComponent as AlignJustify } from "../assets/icons/align_justify.svg";
import { ReactComponent as AlignLeft } from "../assets/icons/align_left.svg";
import { ReactComponent as AlignRight } from "../assets/icons/align_right.svg";

import styled from "styled-components";

import {
  setData,
  hasBlock,
  hasMark,
  hasLinks,
  hasAlignment,
  wrapLink,
  unwrapLink,
  DEFAULT_NODE
} from "../helpers";
/**
 * Define the default node type.
 *
 * @type {String}
 */

const Button = styled.span`
  cursor: pointer;
  padding: 10px 7px 10px 7px;

  &:hover {
    svg {
      color: #fff;
    }
  }
`;

const Icon = styled.span`
  font-size: 18px;
  display: block;

  svg {
    height: 17px;
    box-sizing: content-box;
    background-size: cover;
    color: ${props => (props.active ? "white" : "#aaa")};
  }
`;

/**
 * The hovering menu.
 *
 * @type {Component}
 */

/**
 * Give the menu some styles.
 *
 * @type {Component}
 */

const MenuArrow = styled.div`
  position: absolute;
  bottom: -10px;
  left: 50%;
  clip: rect(10px 20px 20px 0);
  transform: translate(-50, -50);
  margin-left: -10px !important;
`;

const HelperInput = styled.input`
  display: block;
  margin: 10px;
  width: 160px;
  background: none;
  color: #fff;
  padding: 3px;
  border: none;
  caret-color: #fff;
  outline: none;

  &::placeholder {
    color: #e3e3e3;
  }
`;

const HelperCloseButton = styled.button`
  background: none;
  border: none;
  color: #fff;
  padding: 10px;
  cursor: pointer;
`;

const Arrow = styled.span`
  display: block;
  width: 20px;
  height: 20px;
  background-color: #262625;
  -webkit-transform: rotate(45deg) scale(0.5);
  transform: rotate(45deg) scale(0.5);
`;

const StyledMenu = styled.div`
  padding-left: 5px;
  padding-right: 5px;
  position: absolute;
  z-index: 9999;
  top: -10000px;
  left: -10000px;
  margin-top: -6px;
  opacity: 0;
  background-image: linear-gradient(to bottom, rgba(49, 49, 47, 0.99), #262625);
  border-radius: 4px;
  transition: opacity 0.75s;

  & > * {
    display: inline-block;
  }
`;

export default class HoverMenu extends React.Component {
  state = {
    ssrDone: false,
    showHelperInput: false
  };

  componentDidMount() {
    this.setState({ ssrDone: true });
  }

  helperInputKeydown = e => {
    const { editor, onToggleLinkVisibility } = this.props;
    if (e.key === "Enter") {
      const { value: href } = e.target;
      editor.command(wrapLink, href);
      setTimeout(() => {
        this.setState({ showHelperInput: false }, () => {
          onToggleLinkVisibility();
        });
      }, 20);
    }
  };

  /**
   * Render.
   *
   * @return {Element}
   */
  render() {
    const {
      className,
      innerRef,
      onToggleLinkVisibility,
      onMenuReposition
    } = this.props;
    const { ssrDone, showHelperInput } = this.state;

    // To prevent errors on SSR due to window not being available
    if (!ssrDone) {
      return null;
    }

    const root = window.document.getElementsByTagName("body")[0];

    return ReactDOM.createPortal(
      <StyledMenu
        onBlur={() => {
          this.setState({ showHelperInput: false }, () => {
            onToggleLinkVisibility();
          });
        }}
        className={className}
        ref={innerRef}
      >
        {showHelperInput ? (
          <Fragment>
            <HelperInput
              type="text"
              placeholder="Copy or type the link here"
              onKeyDown={this.helperInputKeydown}
            />
            <HelperCloseButton
              onClick={() => {
                this.setState({ showHelperInput: false }, () => {
                  onToggleLinkVisibility();
                  onMenuReposition();
                });
              }}
            >
              x
            </HelperCloseButton>
          </Fragment>
        ) : (
          this.renderButtons()
        )}
        <MenuArrow>
          <Arrow />
        </MenuArrow>
      </StyledMenu>,
      root
    );
  }

  renderButtons = () => {
    return (
      <Fragment>
        {this.renderMarkButton("bold", FormatBold)}
        {this.renderMarkButton("italic", FormatItalic)}
        {this.renderMarkButton("underline", FormatUnderline)}
        {this.renderBlockButton("heading-one", FormatH1)}
        {this.renderBlockButton("heading-two", FormatH2)}
        {this.renderBlockButton("block-quote", FormatQuote)}
        {this.renderLinkButton(FormatLink)}
        {this.renderBlockButton("numbered-list", FormatList)}
        {this.renderBlockButton("bulleted-list", FormatListUl)}

        {/* Comming Soon*/}
        {/* {this.renderAlignButton("align_left", AlignLeft)}
        {this.renderAlignButton("align_center", AlignCenter)}
        {this.renderAlignButton("align_right", AlignRight)}
        {this.renderAlignButton("align_justify", AlignJustify)} */}
      </Fragment>
    );
  };

  /**
   * Render a mark-toggling toolbar button.
   *
   * @param {String} type
   * @param {String} icon
   * @return {Element}
   */

  renderMarkButton = (type, Image) => {
    const { editor } = this.props;
    const isActive = hasMark(editor, type);

    return (
      <Button
        reversed
        active={isActive}
        onMouseDown={event => this.onClickMark(event, type)}
      >
        <Icon active={isActive}>
          <Image />
        </Icon>
      </Button>
    );
  };

  /**
   * Render a link-toggling toolbar button.
   *
   * @param {String} type
   * @param {String} icon
   * @return {Element}
   */

  renderLinkButton = Image => {
    const { editor } = this.props;
    const isActive = hasLinks(editor);

    return (
      <Button
        reversed
        active={isActive}
        onMouseDown={event => this.onClickLink(event, isActive)}
      >
        <Icon active={isActive}>
          <Image />
        </Icon>
      </Button>
    );
  };

  /**
   * Render a align-toggling toolbar button.
   *
   * @param {String} type
   * @param {String} icon
   * @return {Element}
   */

  renderAlignButton = (type, Image) => {
    const { editor } = this.props;
    const isActive = hasAlignment(editor, type);

    return (
      <Button
        active={isActive}
        onMouseDown={event => this.onClickAlign(event, type)}
      >
        <Icon active={isActive}>
          <Image />
        </Icon>
      </Button>
    );
  };

  /**
   * Render a block-toggling toolbar button.
   *
   * @param {String} type
   * @param {String} icon
   * @return {Element}
   */

  renderBlockButton = (type, Image) => {
    const { editor } = this.props;
    let isActive = hasBlock(editor, type);

    const {
      value: { document, blocks }
    } = editor;

    if (["numbered-list", "bulleted-list"].includes(type)) {
      if (blocks.size > 0) {
        const parent = document.getParent(blocks.first().key);
        isActive =
          hasBlock(editor, "list-item") && parent && parent.type === type;
      }
    }

    return (
      <Button
        active={isActive}
        onMouseDown={event => this.onClickBlock(event, type)}
      >
        <Icon active={isActive}>
          <Image />
        </Icon>
      </Button>
    );
  };

  /**
   * When an align button is clicked, change the current data value.
   *
   * @param {Event} event
   * @param {String} type
   */

  onClickAlign = (event, alignment) => {
    event.preventDefault();
    const { editor } = this.props;
    const {
      value: { blocks }
    } = editor;

    blocks.forEach(block => {
      setData(editor, block, { alignment });
    });
  };

  /**
   * When a mark button is clicked, toggle the current mark.
   *
   * @param {Event} event
   * @param {String} type
   */

  onClickMark = (event, type) => {
    event.preventDefault();
    const { editor } = this.props;
    editor.toggleMark(type);
  };

  /**
   * When a Link button is clicked, open the helper input
   *
   * @param {Event} event
   */

  onClickLink = (event, hasLinks) => {
    event.preventDefault();
    const { editor, onToggleLinkVisibility } = this.props;

    if (hasLinks) {
      editor.command(unwrapLink);
    } else {
      this.setState({ showHelperInput: true }, () => {
        onToggleLinkVisibility();
      });
    }
  };

  /**
   * When a block button is clicked, toggle the block type.
   *
   * @param {Event} event
   * @param {String} type
   */

  onClickBlock = (event, type) => {
    event.preventDefault();
    const { editor } = this.props;
    const { value } = editor;
    const { document } = value;

    // Handle everything but list buttons.
    if (type !== "bulleted-list" && type !== "numbered-list") {
      const isActive = hasBlock(editor, type);
      const isList = hasBlock(editor, "list-item");

      if (isList) {
        editor
          .setBlocks(isActive ? DEFAULT_NODE : type)
          .unwrapBlock("bulleted-list")
          .unwrapBlock("numbered-list");
      } else {
        editor.setBlocks(isActive ? DEFAULT_NODE : type);
      }
    } else {
      // Handle the extra wrapping required for list buttons.
      const isList = hasBlock(editor, "list-item");
      const isType = value.blocks.some(block => {
        return !!document.getClosest(block.key, parent => parent.type === type);
      });
      if (isList && isType) {
        editor
          .setBlocks(DEFAULT_NODE)
          .unwrapBlock("bulleted-list")
          .unwrapBlock("numbered-list");
      } else if (isList) {
        editor
          .unwrapBlock(
            type === "bulleted-list" ? "numbered-list" : "bulleted-list"
          )
          .wrapBlock(type);
      } else {
        editor.setBlocks("list-item").wrapBlock(type);
      }
    }
  };
}
